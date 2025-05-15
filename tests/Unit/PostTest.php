<?php

namespace Tests\Unit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Tests\TestCase;
use App\Models\Comment;
use App\Models\User;



class PostTest extends TestCase
{
    
    /**
     * A basic unit test example.
     */
    // public function test_example(): void
    // {
    //     $comment = Comment::factory('App\Models\Comment')->create([
    //         'comment'=>'nice',
    //         'user_id'=>rand(1,100)
    //     ]);

    //     $this->assertInstanceOf(User::class, $comment->user);
    
    // }

    public function test_input_missing_a_title_is_rejected()
    {
        $response = $this->post(route('post.store'), ['title'=>'test']);
        $response->assertRedirect();
        $response->assertSessionHasErrors();
    }
}
