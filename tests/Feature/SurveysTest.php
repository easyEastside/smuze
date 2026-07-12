<?php

use App\Models\Survey;
use App\Models\SurveyOption;
use App\Models\SurveyQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createSurveyWithQuestion(array $surveyAttributes = []): array
{
    $survey = Survey::factory()->create($surveyAttributes);
    $question = SurveyQuestion::factory()->for($survey)->create(['question' => 'Which color do you prefer?']);
    $firstOption = SurveyOption::factory()->for($question, 'question')->create(['label' => 'Blue', 'position' => 1]);
    $secondOption = SurveyOption::factory()->for($question, 'question')->create(['label' => 'Red', 'position' => 2]);

    return [$survey, $question, $firstOption, $secondOption];
}

test('guest cannot view surveys', function () {
    $this->get(route('surveys.index'))->assertRedirectToRoute('login');
});

test('user can view active surveys', function () {
    $startsAt = now()->subHour()->seconds(0);
    $endsAt = now()->addDay()->seconds(0);
    [$survey] = createSurveyWithQuestion([
        'title' => 'Product feedback',
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
    ]);
    Survey::factory()->inactive()->create(['title' => 'Hidden survey']);

    $this->actingAs(User::factory()->create())
        ->get(route('surveys.index'))
        ->assertSuccessful()
        ->assertSee('Product feedback')
        ->assertSeeText('Availability')
        ->assertSeeText($startsAt->format('M j, Y H:i').' - '.$endsAt->format('M j, Y H:i'))
        ->assertDontSee('Hidden survey');

    expect($survey->responses()->count())->toBe(0);
});

test('user can take a survey', function () {
    [$survey, $question, $option] = createSurveyWithQuestion();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('surveys.responses.store', $survey), [
            'answers' => [
                $question->id => $option->id,
            ],
        ])
        ->assertRedirect(route('surveys.show', $survey, absolute: false));

    $this->assertDatabaseHas('survey_responses', [
        'survey_id' => $survey->id,
        'user_id' => $user->id,
    ]);

    $this->assertDatabaseHas('survey_answers', [
        'survey_question_id' => $question->id,
        'survey_option_id' => $option->id,
    ]);
});

test('user sees results with a chart after taking a survey', function () {
    [$survey, $question, $firstOption, $secondOption] = createSurveyWithQuestion();
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $otherResponse = $survey->responses()->create(['user_id' => $otherUser->id]);
    $otherResponse->answers()->create([
        'survey_question_id' => $question->id,
        'survey_option_id' => $secondOption->id,
    ]);

    $this->actingAs($user)->post(route('surveys.responses.store', $survey), [
        'answers' => [$question->id => $firstOption->id],
    ]);

    $this->actingAs($user)
        ->get(route('surveys.show', $survey))
        ->assertSuccessful()
        ->assertSeeText('Results')
        ->assertSeeText('Your answer')
        ->assertSeeText('Blue')
        ->assertSeeText('Red')
        ->assertSeeText('1 vote | 50%')
        ->assertSee('style="width: 50%"', false);
});

test('user cannot take the same survey twice', function () {
    [$survey, $question, $option] = createSurveyWithQuestion();
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('surveys.responses.store', $survey), [
        'answers' => [$question->id => $option->id],
    ]);

    $this->actingAs($user)
        ->post(route('surveys.responses.store', $survey), [
            'answers' => [$question->id => $option->id],
        ])
        ->assertSessionHasErrors(['survey']);

    expect($survey->responses()->count())->toBe(1);
});

test('inactive survey cannot be answered', function () {
    [$survey, $question, $option] = createSurveyWithQuestion(['is_active' => false]);

    $this->actingAs(User::factory()->create())
        ->post(route('surveys.responses.store', $survey), [
            'answers' => [$question->id => $option->id],
        ])
        ->assertSessionHasErrors(['survey']);
});

test('answer option must belong to the question', function () {
    [$survey, $question] = createSurveyWithQuestion();
    [, , $otherOption] = createSurveyWithQuestion();

    $this->actingAs(User::factory()->create())
        ->post(route('surveys.responses.store', $survey), [
            'answers' => [$question->id => $otherOption->id],
        ])
        ->assertSessionHasErrors(["answers.{$question->id}"]);
});

test('admin can create a survey', function () {
    $role = Role::create(['name' => 'admin']);
    $permission = Permission::create(['name' => 'access-admin']);
    $role->givePermissionTo($permission);

    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.surveys.store'), [
            'title' => 'Website feedback',
            'description' => 'Tell us what you think.',
            'is_active' => '1',
            'questions' => [
                [
                    'question' => 'How do you rate the website?',
                    'options' => [
                        ['label' => 'Excellent'],
                        ['label' => 'Good'],
                        ['label' => 'Okay'],
                        ['label' => 'Bad'],
                        ['label' => 'Very bad'],
                        ['label' => 'No opinion'],
                    ],
                ],
            ],
        ])
        ->assertRedirect(route('admin.surveys.index', absolute: false));

    $this->assertDatabaseHas('surveys', ['title' => 'Website feedback']);
    $this->assertDatabaseHas('survey_questions', ['question' => 'How do you rate the website?']);
    $this->assertDatabaseHas('survey_options', ['label' => 'Excellent']);
    $this->assertDatabaseHas('survey_options', ['label' => 'No opinion']);
    $this->assertDatabaseCount('survey_options', 6);
});

test('admin can view survey results', function () {
    [$survey, $question, $option] = createSurveyWithQuestion(['title' => 'Results survey']);
    $user = User::factory()->create(['name' => 'Participant']);
    $response = $survey->responses()->create(['user_id' => $user->id]);
    $response->answers()->create([
        'survey_question_id' => $question->id,
        'survey_option_id' => $option->id,
    ]);

    $role = Role::create(['name' => 'admin']);
    $permission = Permission::create(['name' => 'access-admin']);
    $role->givePermissionTo($permission);

    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.surveys.show', $survey))
        ->assertSuccessful()
        ->assertSee('Results survey')
        ->assertSee('Participant')
        ->assertSee('Blue');
});

test('admin can see survey open period in overview', function () {
    $startsAt = now()->subDay()->seconds(0);
    $endsAt = now()->addWeek()->seconds(0);
    createSurveyWithQuestion([
        'title' => 'Scheduled survey',
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
    ]);

    $role = Role::create(['name' => 'admin']);
    $permission = Permission::create(['name' => 'access-admin']);
    $role->givePermissionTo($permission);

    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.surveys.index'))
        ->assertSuccessful()
        ->assertSeeText('Availability')
        ->assertSeeText('Scheduled survey')
        ->assertSeeText($startsAt->format('M j, Y H:i').' - '.$endsAt->format('M j, Y H:i'));
});
