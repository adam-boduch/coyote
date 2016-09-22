<?php

namespace Coyote\Http\Controllers\Profile;

use Coyote\Http\Controllers\Controller;
use Coyote\Http\Controllers\User\UserMenuTrait;
use Coyote\Repositories\Contracts\PostRepositoryInterface as PostRepository;
use Coyote\Repositories\Contracts\ReputationRepositoryInterface as ReputationRepository;
use Coyote\Repositories\Contracts\UserRepositoryInterface as UserRepository;
use Coyote\User;

class HomeController extends Controller
{
    use UserMenuTrait;

    /**
     * @var UserRepository
     */
    private $user;

    /**
     * @var ReputationRepository
     */
    private $reputation;

    /**
     * @var PostRepository
     */
    private $post;

    /**
     * HomeController constructor.
     *
     * @param UserRepository $user
     * @param ReputationRepository $reputation
     * @param PostRepository $post
     */
    public function __construct(UserRepository $user, ReputationRepository $reputation, PostRepository $post)
    {
        parent::__construct();

        $this->user = $user;
        $this->reputation = $reputation;
        $this->post = $post;
    }

    /**
     * @param \Coyote\User $user
     * @param string $tab
     * @return \Illuminate\View\View
     */
    public function index($user, $tab = 'reputation')
    {
        $this->breadcrumb->push($user->name, route('profile', ['user' => $user->id]));

        return $this->view('profile.home')->with([
            'top_menu'      => $this->getUserMenu(),
            'user'          => $user,
            'skills'        => $user->skills()->orderBy('order')->get(),
            'tab'           => strtolower($tab),
            'module'        => $this->$tab($user)
        ]);
    }

    /**
     * @param User $user
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    private function reputation(User $user)
    {
        return view('profile.partials.reputation', [
            'user'          => $user,
            'rank'          => $this->user->rank($user->id),
            'total_users'   => $this->user->countUsersWithReputation(),
            'reputation'    => $this->reputation->takeForUser($user->id),
            'chart'         => $this->reputation->chart($user->id),
        ]);
    }

    /**
     * Singular name of method because of backward compatibility.
     *
     * @param User $user
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    private function post(User $user)
    {
        return view('profile.partials.posts', [
            'user' => $user,
            'pie' => $this->post->pieChart($user->id)
        ]);
    }
}
