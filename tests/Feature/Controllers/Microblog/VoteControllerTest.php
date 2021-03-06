<?php

namespace Tests\Feature\Controllers\Microblog;

use Coyote\Microblog;
use Coyote\Notifications\Microblog\VotedNotification;
use Coyote\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class VoteControllerTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    /**
     * @var User
     */
    private $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = factory(User::class)->create();
    }

    public function testFailedVote()
    {
        $microblog = factory(Microblog::class)->create();
        $response = $this->json('POST', '/Mikroblogi/Vote/' . $microblog->id);

        $response->assertJson(['message' => 'Musisz być zalogowany, aby oddać ten głos.']);
    }

    public function testSuccessfulVote()
    {
        Notification::fake();
        $microblog = factory(Microblog::class)->create();

        $response = $this->actingAs($this->user)->json('POST', '/Mikroblogi/Vote/' . $microblog->id);

        Notification::assertSentTo(
            [$microblog->user], VotedNotification::class
        );

        $response->assertJson(['count' => 1, 'users' => [$this->user->name]]);

        $response = $this->actingAs($this->user)->json('POST', '/Mikroblogi/Vote/' . $microblog->id);
        $response->assertJson([]);
    }

    public function testGetAllVoters()
    {
        /** @var Microblog $microblog */
        $microblog = factory(Microblog::class)->create();

        $users = factory(User::class, 11)->create()->each(function (User $user) use ($microblog) {
            $microblog->voters()->create(['user_id' => $user->id, 'ip' => $this->faker->ipv4]);
        });

        $response = $this->get('/Mikroblogi/Voters/' . $microblog->id);

        $response->assertJson(['count' => 11, 'users' => $users->pluck('name')->slice(0, 10)->concat(['...'])->toArray()]);
    }
}
