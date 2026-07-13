package main

import (
	"encoding/base64"
	"encoding/json"
	"errors"
	"path/filepath"
	"regexp"
	"strings"
)

var fileProtectedDeletePaths = map[string]bool{"/": true, "/var": true, "/var/www": true, "/home": true, "/etc": true, "/tmp": true, "/root": true}
var fileModePattern = regexp.MustCompile(`^0?[0-7]{3,4}$`)

func fileActionDefinitions() []actionDefinition {
	return []actionDefinition{filesListAction(), filesReadAction(), filesWriteAction(), filesMkdirAction(), filesTouchAction(), filesRenameAction(), filesChmodAction(), filesDeleteAction(), filesUploadAction(), filesDownloadAction()}
}

func filesListAction() actionDefinition {
	return filePythonAction("files.list", filesListPython(), 30)
}

func filesReadAction() actionDefinition {
	return filePythonAction("files.read", filesReadPython(), 30)
}

func filesWriteAction() actionDefinition {
	return filePythonAction("files.write", filesWritePython(), 30)
}

func filesMkdirAction() actionDefinition {
	return filePythonAction("files.mkdir", filesMkdirPython(), 30)
}

func filesTouchAction() actionDefinition {
	return filePythonAction("files.touch", filesTouchPython(), 30)
}

func filesRenameAction() actionDefinition {
	return filePythonAction("files.rename", filesRenamePython(), 30)
}

func filesChmodAction() actionDefinition {
	return filePythonAction("files.chmod", filesChmodPython(), 30)
}

func filesDeleteAction() actionDefinition {
	return filePythonAction("files.delete", filesDeletePython(), 30)
}

func filesUploadAction() actionDefinition {
	return filePythonAction("files.upload", filesUploadPython(), 120)
}

func filesDownloadAction() actionDefinition {
	return filePythonAction("files.download", filesDownloadPython(), 120)
}

func filePythonAction(name string, script string, timeout int) actionDefinition {
	return actionDefinition{
		Name: name,
		BuildCommand: func(payload map[string]any) (string, error) {
			if err := validateFilePayload(name, payload); err != nil {
				return "", err
			}

			encodedPayload, err := encodedJSONPayload(payload)
			if err != nil {
				return "", err
			}

			return "SMUZE_FILE_PAYLOAD=" + shellQuote(encodedPayload) + " python3 - <<'PY'\n" + filePythonPrelude() + script + "\nPY", nil
		},
		Timeout: timeout,
		UseSudo: true,
	}
}

func validateFilePayload(action string, payload map[string]any) error {
	pathKeys := []string{"path"}
	if action == "files.rename" {
		pathKeys = []string{"path", "new_path"}
	}

	for _, key := range pathKeys {
		path, err := payloadString(payload, key)
		if err != nil {
			return err
		}
		if err := validateFilePath(path); err != nil {
			return err
		}
	}

	if action == "files.delete" {
		path, _ := payloadString(payload, "path")
		if fileProtectedDeletePaths[filepath.Clean(path)] {
			return errors.New("refusing to delete protected path")
		}
	}

	if action == "files.chmod" {
		mode, err := payloadString(payload, "mode")
		if err != nil {
			return err
		}
		if !fileModePattern.MatchString(strings.TrimSpace(mode)) {
			return errors.New("mode must be an octal chmod value")
		}
	}

	return nil
}

func validateFilePath(path string) error {
	path = strings.TrimSpace(path)
	if path == "" || strings.ContainsAny(path, "\x00\r\n") || strings.Contains(path, "..") || !strings.HasPrefix(path, "/") {
		return errors.New("path must be an absolute safe path")
	}

	cleaned := filepath.Clean(path)
	if cleaned == "." || !strings.HasPrefix(cleaned, "/") {
		return errors.New("path must be an absolute safe path")
	}

	return nil
}

func encodedJSONPayload(payload map[string]any) (string, error) {
	data, err := json.Marshal(payload)
	if err != nil {
		return "", err
	}

	return base64.StdEncoding.EncodeToString(data), nil
}

func filePythonPrelude() string {
	return `import base64, grp, json, os, pwd, shutil, stat, sys, time

PROTECTED_DELETE = set(['/', '/var', '/var/www', '/home', '/etc', '/tmp', '/root'])
payload = json.loads(base64.b64decode(os.environ['SMUZE_FILE_PAYLOAD']).decode('utf-8'))

def fail(message):
    print(message, file=sys.stderr)
    sys.exit(1)

def safe_path(value, allow_root=False):
    path = str(value or '').strip()
    if not path or '\x00' in path or '\n' in path or '\r' in path or '..' in path or not path.startswith('/'):
        fail('unsafe path')
    clean = os.path.normpath(path)
    if clean == '/' and allow_root:
        return clean
    if not clean.startswith('/'):
        fail('unsafe path')
    return clean

def entry(path):
    st = os.lstat(path)
    mode = stat.filemode(st.st_mode)
    if stat.S_ISDIR(st.st_mode):
        kind = 'directory'
    elif stat.S_ISLNK(st.st_mode):
        kind = 'symlink'
    else:
        kind = 'file'
    try:
        owner = pwd.getpwuid(st.st_uid).pw_name
    except KeyError:
        owner = str(st.st_uid)
    try:
        group = grp.getgrgid(st.st_gid).gr_name
    except KeyError:
        group = str(st.st_gid)
    return {
        'name': os.path.basename(path.rstrip('/')) or path,
        'path': path,
        'type': kind,
        'size': st.st_size,
        'mode': mode,
        'owner': owner,
        'group': group,
        'modified': time.strftime('%Y-%m-%d %H:%M:%S', time.localtime(st.st_mtime)),
    }
`
}

func filesListPython() string {
	return `path = safe_path(payload.get('path', '/var/www'), allow_root=True)
if path == '/':
    pass
if not os.path.isdir(path):
    fail('path is not a directory')
items = []
for name in os.listdir(path):
    child = os.path.join(path, name)
    try:
        items.append(entry(child))
    except OSError:
        pass
items.sort(key=lambda item: (item['type'] != 'directory', item['name'].lower()))
print(json.dumps({'path': path, 'entries': items}))`
}

func filesReadPython() string {
	return `path = safe_path(payload.get('path'))
if not os.path.isfile(path):
    fail('path is not a file')
size = os.path.getsize(path)
if size > 1024 * 1024:
    fail('file is larger than 1 MB; please download it')
with open(path, 'rb') as handle:
    data = handle.read()
try:
    content = data.decode('utf-8')
except UnicodeDecodeError:
    fail('file is not valid UTF-8; please download it')
print(json.dumps({'path': path, 'size': size, 'content': content}))`
}

func filesWritePython() string {
	return `path = safe_path(payload.get('path'))
content = str(payload.get('content', ''))
with open(path, 'w', encoding='utf-8') as handle:
    handle.write(content)
print(json.dumps({'path': path, 'size': os.path.getsize(path)}))`
}

func filesMkdirPython() string {
	return `path = safe_path(payload.get('path'))
os.makedirs(path, exist_ok=False)
print(json.dumps({'path': path}))`
}

func filesTouchPython() string {
	return `path = safe_path(payload.get('path'))
flags = os.O_CREAT | os.O_EXCL | os.O_WRONLY
fd = os.open(path, flags, 0o644)
os.close(fd)
print(json.dumps({'path': path}))`
}

func filesRenamePython() string {
	return `path = safe_path(payload.get('path'))
new_path = safe_path(payload.get('new_path'))
os.rename(path, new_path)
print(json.dumps({'path': new_path}))`
}

func filesChmodPython() string {
	return `path = safe_path(payload.get('path'))
mode_text = str(payload.get('mode', '')).strip()
if not mode_text or any(ch not in '01234567' for ch in mode_text) or len(mode_text) not in (3, 4):
    fail('invalid chmod mode')
mode = int(mode_text, 8)
os.chmod(path, mode)
print(json.dumps({'path': path, 'mode': oct(mode)}))`
}

func filesDeletePython() string {
	return `path = safe_path(payload.get('path'))
if os.path.normpath(path) in PROTECTED_DELETE:
    fail('refusing to delete protected path')
if os.path.isdir(path) and not os.path.islink(path):
    if not bool(payload.get('recursive', False)):
        fail('recursive flag required for directories')
    shutil.rmtree(path)
else:
    os.remove(path)
print(json.dumps({'path': path}))`
}

func filesUploadPython() string {
	return `path = safe_path(payload.get('path'))
content = base64.b64decode(str(payload.get('content_base64', '')), validate=True)
with open(path, 'wb') as handle:
    handle.write(content)
print(json.dumps({'path': path, 'size': os.path.getsize(path)}))`
}

func filesDownloadPython() string {
	return `path = safe_path(payload.get('path'))
if not os.path.isfile(path):
    fail('path is not a file')
with open(path, 'rb') as handle:
    content = base64.b64encode(handle.read()).decode('ascii')
print(json.dumps({'path': path, 'size': os.path.getsize(path), 'content_base64': content}))`
}
