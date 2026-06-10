<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_when_visiting_tasks_workspace(): void
    {
        $this->get(route('tasks.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_render_the_task_view_component_and_see_their_data_payload(): void
    {
        // 1. Create a fake user profile record inside our in-memory SQLite database
        $user = User::factory()->create();

        // 2. Create a task explicitly bound to this specific user profile
        $task = Task::factory()->create(['user_id' => $user->id, 'title' => 'Learn Pest Testing']);

        // 3. Act as the logged-in user, hit the route, and run evaluations
        $this->actingAs($user)
            ->get(route('tasks.index'))
            ->assertOk()
            // Here we test the Inertia bridge explicitly!
            ->assertInertia(
                fn ($page) => $page
                    ->component('Tasks/Index') // Asserts the exact Vue file path is used
                    ->has('tasks.data', 1)      // Asserts there is exactly 1 row item inside the paginated array
                    ->where('tasks.data.0.title', 'Learn Pest Testing') // Verifies the exact content reached Vue
            );
    }

    public function test_users_cannot_alter_or_check_off_tasks_belonging_to_someone_else(): void
    {
        // 1. Create two distinct user records
        $userOne = User::factory()->create();
        $userTwo = User::factory()->create();

        // 2. Assign a task exclusively to User One
        $taskOwnedByUserOne = Task::factory()->create([
            'user_id' => $userOne->id,
            'title' => 'Private Task Data',
            'is_completed' => false,
        ]);

        // 3. Try to execute a PUT state mutation acting maliciously as User Two
        $this->actingAs($userTwo)
            ->put(route('tasks.update', $taskOwnedByUserOne->id))
            ->assertForbidden(); // Asserts that our Authorization Gate safely returned a 403 response!

        // 4. Verify the database record state inside SQLite was completely untouched
        $this->assertDatabaseHas('tasks', [
            'id' => $taskOwnedByUserOne->id,
            'is_completed' => false,
        ]);
    }
}
