<?php

use App\Models\Message;
use App\Models\MessageThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected from messages to login', function () {
    $this->get(route('messages.index'))->assertRedirectToRoute('login');
});

test('authenticated users can see empty inbox', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('messages.index'))
        ->assertSuccessful()
        ->assertSee('No message threads yet');
});

test('user can start multiple threads with same recipient', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create(['name' => 'Recipient User']);

    $this->actingAs($sender)
        ->post(route('messages.store'), [
            'recipient_id' => $recipient->id,
            'subject' => 'First topic',
            'body' => 'First thread body.',
        ])
        ->assertRedirect();

    $this->actingAs($sender)
        ->post(route('messages.store'), [
            'recipient_id' => $recipient->id,
            'subject' => 'Second topic',
            'body' => 'Second thread body.',
        ])
        ->assertRedirect();

    $this->assertDatabaseCount('message_threads', 2);
    $this->assertDatabaseHas('message_threads', ['subject' => 'First topic']);
    $this->assertDatabaseHas('message_threads', ['subject' => 'Second topic']);
    $this->assertDatabaseCount('messages', 2);

    $this->actingAs($sender)
        ->get(route('messages.index'))
        ->assertSuccessful()
        ->assertSee('First topic')
        ->assertSee('Second topic')
        ->assertSee('Recipient User');
});

test('user cannot start a thread with themselves', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('messages.store'), [
            'recipient_id' => $user->id,
            'subject' => 'Self topic',
            'body' => 'This should fail.',
        ])
        ->assertSessionHasErrors(['recipient_id']);

    $this->assertDatabaseCount('message_threads', 0);
});

test('thread participants can read and reply to a thread', function () {
    $sender = User::factory()->create(['name' => 'Sender User']);
    $recipient = User::factory()->create(['name' => 'Recipient User']);
    $thread = createThread($sender, $recipient, 'Support topic');

    Message::create([
        'message_thread_id' => $thread->id,
        'sender_id' => $sender->id,
        'body' => 'Initial question.',
    ]);

    $this->actingAs($recipient)
        ->get(route('messages.show', $thread))
        ->assertSuccessful()
        ->assertSee('Support topic')
        ->assertSee('Initial question.')
        ->assertSee('Sender User');

    $this->actingAs($recipient)
        ->post(route('messages.reply', $thread), ['body' => 'Answer from recipient.'])
        ->assertRedirect(route('messages.show', $thread, absolute: false));

    $this->assertDatabaseHas('messages', [
        'message_thread_id' => $thread->id,
        'sender_id' => $recipient->id,
        'body' => 'Answer from recipient.',
    ]);
});

test('non participants cannot open or reply to a thread', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $outsider = User::factory()->create();
    $thread = createThread($sender, $recipient, 'Private topic');

    $this->actingAs($outsider)
        ->get(route('messages.show', $thread))
        ->assertForbidden();

    $this->actingAs($outsider)
        ->post(route('messages.reply', $thread), ['body' => 'Intrusion.'])
        ->assertForbidden();
});

test('opening a thread marks received messages as read', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $thread = createThread($sender, $recipient, 'Read state topic');

    $receivedMessage = Message::create([
        'message_thread_id' => $thread->id,
        'sender_id' => $sender->id,
        'body' => 'Unread message.',
    ]);

    $ownMessage = Message::create([
        'message_thread_id' => $thread->id,
        'sender_id' => $recipient->id,
        'body' => 'Own unread message should stay unchanged.',
    ]);

    $this->actingAs($recipient)
        ->get(route('messages.show', $thread))
        ->assertSuccessful();

    expect($receivedMessage->fresh()->read_at)->not->toBeNull()
        ->and($ownMessage->fresh()->read_at)->toBeNull();
});

function createThread(User $firstUser, User $secondUser, string $subject): MessageThread
{
    $participantIds = collect([$firstUser->id, $secondUser->id])->sort()->values();

    return MessageThread::create([
        'participant_one_id' => $participantIds[0],
        'participant_two_id' => $participantIds[1],
        'subject' => $subject,
    ]);
}
