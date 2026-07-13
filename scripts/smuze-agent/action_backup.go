package main

import (
	"encoding/base64"
	"encoding/json"
	"errors"
	"regexp"
	"strconv"
	"strings"
)

var backupFilenamePattern = regexp.MustCompile(`^[A-Za-z0-9._-]+\.tar\.gz$`)

func backupRunAction() actionDefinition {
	return backupPythonAction("backup.run", backupRunPython(), 600)
}

func backupListAction() actionDefinition {
	return backupPythonAction("backup.list", backupListPython(), 60)
}

func backupDeleteAction() actionDefinition {
	return backupPythonAction("backup.delete", backupDeletePython(), 60)
}

func backupRestoreAction() actionDefinition {
	return backupPythonAction("backup.restore", backupRestorePython(), 600)
}

func backupPruneAction() actionDefinition {
	return backupPythonAction("backup.prune", backupPrunePython(), 120)
}

func backupPythonAction(name string, script string, timeout int) actionDefinition {
	return actionDefinition{
		Name: name,
		BuildCommand: func(payload map[string]any) (string, error) {
			if err := validateBackupPayload(name, payload); err != nil {
				return "", err
			}

			encodedPayload, err := encodedBackupPayload(payload)
			if err != nil {
				return "", err
			}

			return "SMUZE_BACKUP_PAYLOAD=" + shellQuote(encodedPayload) + " python3 - <<'PY'\n" + backupPythonPrelude() + script + "\nPY", nil
		},
		Timeout: timeout,
		UseSudo: true,
	}
}

func validateBackupPayload(action string, payload map[string]any) error {
	if action == "backup.run" || action == "backup.list" || action == "backup.prune" {
		if _, err := backupID(payload); err != nil {
			return err
		}
	}

	if action == "backup.run" {
		backupType, err := payloadString(payload, "type")
		if err != nil {
			return err
		}
		if backupType != "mysql" && backupType != "files" && backupType != "both" {
			return errors.New("type must be mysql, files, or both")
		}

		storage, err := payloadString(payload, "storage")
		if err != nil {
			return err
		}
		if storage != "local" && storage != "s3" {
			return errors.New("storage must be local or s3")
		}
		if err := validateBackupTargets(payload, backupType); err != nil {
			return err
		}
	}

	if action == "backup.delete" || action == "backup.restore" {
		filename, err := payloadString(payload, "filename")
		if err != nil {
			return err
		}
		if !backupFilenamePattern.MatchString(strings.TrimSpace(filename)) || strings.Contains(filename, "..") {
			return errors.New("filename must be a backup archive name")
		}
	}

	if action == "backup.restore" {
		backupType, err := payloadString(payload, "type")
		if err != nil {
			return err
		}
		if backupType != "mysql" && backupType != "files" && backupType != "both" {
			return errors.New("type must be mysql, files, or both")
		}
		if err := validateBackupTargets(payload, backupType); err != nil {
			return err
		}
	}

	if action == "backup.prune" {
		retention, err := payloadString(payload, "retention_days")
		if err != nil {
			return err
		}
		days, err := strconv.Atoi(strings.TrimSpace(retention))
		if err != nil || days < 1 || days > 365 {
			return errors.New("retention_days must be between 1 and 365")
		}
	}

	return nil
}

func backupID(payload map[string]any) (int, error) {
	value, err := payloadString(payload, "backup_id")
	if err != nil {
		return 0, err
	}
	id, err := strconv.Atoi(strings.TrimSpace(value))
	if err != nil || id < 1 {
		return 0, errors.New("backup_id must be a positive integer")
	}

	return id, nil
}

func validateBackupTargets(payload map[string]any, backupType string) error {
	targets, ok := payload["targets"].([]any)
	if !ok || len(targets) == 0 {
		return errors.New("targets must be a non-empty array")
	}

	for _, target := range targets {
		targetString, ok := target.(string)
		if !ok || strings.TrimSpace(targetString) == "" || strings.ContainsAny(targetString, "\x00\r\n") || strings.Contains(targetString, "..") {
			return errors.New("targets must be safe strings")
		}
		if (backupType == "files" || backupType == "both") && strings.HasPrefix(strings.TrimSpace(targetString), "/") {
			if err := validateFilePath(targetString); err != nil {
				return err
			}
		}
		if backupType == "files" && !strings.HasPrefix(strings.TrimSpace(targetString), "/") {
			return errors.New("file backup targets must be absolute paths")
		}
	}

	return nil
}

func encodedBackupPayload(payload map[string]any) (string, error) {
	data, err := json.Marshal(payload)
	if err != nil {
		return "", err
	}

	return base64.StdEncoding.EncodeToString(data), nil
}

func backupPythonPrelude() string {
	return `import base64, glob, json, os, shutil, subprocess, sys, tarfile, tempfile, time

payload = json.loads(base64.b64decode(os.environ['SMUZE_BACKUP_PAYLOAD']).decode('utf-8'))
BASE = '/var/backups/smuze'

def fail(message):
    print(message, file=sys.stderr)
    sys.exit(1)

def backup_id():
    value = int(payload.get('backup_id'))
    if value < 1:
        fail('backup_id must be positive')
    return value

def backup_dir():
    path = os.path.join(BASE, str(backup_id()))
    os.makedirs(path, exist_ok=True)
    return path

def safe_filename(value):
    filename = str(value or '').strip()
    if not filename.endswith('.tar.gz') or '/' in filename or '\\' in filename or '..' in filename:
        fail('unsafe filename')
    return filename

def safe_path(value):
    path = str(value or '').strip()
    if not path or '\x00' in path or '\n' in path or '\r' in path or '..' in path or not path.startswith('/'):
        fail('unsafe path')
    clean = os.path.normpath(path)
    if not clean.startswith('/'):
        fail('unsafe path')
    return clean

def archive_path(filename):
    return os.path.join(backup_dir(), safe_filename(filename))

def add_path(tar, path):
    clean = safe_path(path)
    if not os.path.exists(clean):
        fail('target does not exist: ' + clean)
    tar.add(clean, arcname=clean.lstrip('/'), recursive=True)

def extract_safely(tar, destination):
    destination = os.path.abspath(destination)
    for member in tar.getmembers():
        target = os.path.abspath(os.path.join(destination, member.name))
        if destination == '/':
            if not target.startswith('/'):
                fail('unsafe archive member')
            continue
        if not target.startswith(destination + os.sep) and target != destination:
            fail('unsafe archive member')
    tar.extractall(destination)
`
}

func backupRunPython() string {
	return `backup_type = str(payload.get('type'))
storage = str(payload.get('storage'))
if storage != 'local':
    fail('S3 backups are not supported by this agent yet')
targets = payload.get('targets') or []
directory = backup_dir()
filename = 'backup-%s-%s.tar.gz' % (backup_id(), time.strftime('%Y%m%d%H%M%S'))
path = os.path.join(directory, filename)
tmpdir = tempfile.mkdtemp(prefix='smuze-backup-')
try:
    with tarfile.open(path, 'w:gz') as tar:
        if backup_type in ('files', 'both'):
            for target in targets:
                if str(target).startswith('/'):
                    add_path(tar, target)
        if backup_type in ('mysql', 'both'):
            if shutil.which('mysqldump') is None:
                fail('mysqldump is not installed')
            for database in targets:
                if str(database).startswith('/'):
                    continue
                dump_path = os.path.join(tmpdir, str(database) + '.sql')
                with open(dump_path, 'wb') as handle:
                    result = subprocess.run(['mysqldump', str(database)], stdout=handle, stderr=subprocess.PIPE)
                if result.returncode != 0:
                    fail(result.stderr.decode('utf-8', errors='ignore') or 'mysqldump failed')
                tar.add(dump_path, arcname='mysql/' + os.path.basename(dump_path))
finally:
    shutil.rmtree(tmpdir, ignore_errors=True)
print(json.dumps({'filename': filename, 'size_bytes': os.path.getsize(path), 'storage_path': path}))`
}

func backupListPython() string {
	return `directory = backup_dir()
files = []
for path in sorted(glob.glob(os.path.join(directory, '*.tar.gz')), key=os.path.getmtime, reverse=True):
    files.append({'filename': os.path.basename(path), 'size_bytes': os.path.getsize(path), 'storage_path': path, 'modified': time.strftime('%Y-%m-%d %H:%M:%S', time.localtime(os.path.getmtime(path)))})
print(json.dumps({'files': files}))`
}

func backupDeletePython() string {
	return `path = archive_path(payload.get('filename'))
if os.path.exists(path):
    os.remove(path)
print(json.dumps({'deleted': os.path.basename(path)}))`
}

func backupRestorePython() string {
	return `backup_type = str(payload.get('type'))
path = archive_path(payload.get('filename'))
if not os.path.isfile(path):
    fail('backup archive not found')
if backup_type == 'mysql':
    fail('mysql restore is not supported by this agent yet')
with tarfile.open(path, 'r:gz') as tar:
    extract_safely(tar, '/')
print('Wiederherstellung abgeschlossen.')`
}

func backupPrunePython() string {
	return `directory = backup_dir()
retention_days = int(payload.get('retention_days'))
threshold = time.time() - (retention_days * 86400)
deleted = []
for path in glob.glob(os.path.join(directory, '*.tar.gz')):
    if os.path.getmtime(path) < threshold:
        os.remove(path)
        deleted.append(os.path.basename(path))
print(json.dumps({'deleted': deleted, 'message': 'Alte Backups bereinigt.'}))`
}
