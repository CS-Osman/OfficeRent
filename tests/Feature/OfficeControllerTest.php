<?php

namespace Tests\Feature;

use App\Models\Image;
use App\Models\Office;
use App\Models\Reservation;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OfficeControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
    * @test
    */

    public function itsListsAllOfficesInPaginateWay()
    {
        Office::factory(30)->create();

        $response = $this->get('/api/offices');
        
        $response->assertOk()
            ->assertJsonStructure(['data', 'meta', 'links'])
            ->assertJsonCount(20, 'data')
            ->assertJsonStructure(['data' => ['*' => ['id', 'title']]]);
    }

    /**
    * @test
    */

    public function itsOnlyListsOfficesThatAreNotHiddenAndApproved()
    {
        Office::factory(3)->create();

        Office::factory(3)->create(['hidden' => true]);
        Office::factory(3)->create(['approval_status' => Office::APPROVAL_PENDING]);

        $response = $this->get('/api/offices');
        
        $response->assertOk();

        $response->assertJsonCount(3, 'data');

        $this->assertNotNull($response->json('data')[0]['id']);
    }
   
    /**
    * @test
    */
    public function itFilterByUserId()
    {
        Office::factory(3)->create();

        $host = User::factory()->create();

        $office = Office::factory()->for($host)->create();

        $response = $this->get(
            '/api/offices?user_id='. $host->id
        );
        
        $response->assertOk();
        $response->assertJsonCount(1, 'data');

        $this->assertEquals($office->id, $response->json('data')[0]['id']);
    }

    /**
    * @test
    */
    public function itFilterByVisitorId()
    {
        Office::factory(3)->create();

        $user = User::factory()->create();

        $office = Office::factory()->create();

        Reservation::factory()->for(Office::factory())->create();
        
        Reservation::factory()->for($office)->for($user)->create();

        $response = $this->get(
            '/api/offices?visitor_id='. $user->id
        );
        
        $response->assertOk();
        $response->assertJsonCount(1, 'data');

        $this->assertEquals($office->id, $response->json('data')[0]['id']);
    }

    /**
    * @test
    */
    public function itsIncludeImagesTagsUser()
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create();

        $office = Office::factory()->for($user)->create();
        
        $office->tags()->attach($tag);

        $office->images()->create(['path' => 'image.png']);
        
        $response = $this->get(
            '/api/offices'
        );
        
        $response->assertOk();
        $this->assertCount(1, $response->json('data')[0]['tags']);
        $this->assertCount(1, $response->json('data')[0]['images']);
        $this->assertEquals($user->id, $response->json('data')[0]['user']['id']);
    }

    /**
     * @test
    */
    public function itsReturnTheNumberOfActiveReservation()
    {
        $office = Office::factory()->create();

        Reservation::factory()->for($office)->create(['status' => Reservation::STATUS_ACTIVE]);
        Reservation::factory()->for($office)->create(['status' => Reservation::STATUS_CANCELLED]);

        $response = $this->get(
            '/api/offices'
        );

        $response->assertOk();

        $this->assertEquals(1, $response->json('data')[0]['reservations_count']);
    }

    /**
    * @test
    */
    public function itOrdersByDistanceWhenCoordinatesAreProvided()
    {
        $office1 = Office::factory()->create([
            'lat' => '39.74051727562952',
            'lng' => '-8.770375324893696',
            'title' => 'Leiria'
        ]);

        $office2 = Office::factory()->create([
            'lat' => '39.07753883078113',
            'lng' => '-9.281266331143293',
            'title' => 'Torres Vedras'
        ]);

        $response = $this->get('/api/offices?lat=38.720661384644046&lng=-9.16044783453807');

        $response->assertOk();
        $this->assertEquals('Torres Vedras', $response->json('data')[0]['title']);
        $this->assertEquals('Leiria', $response->json('data')[1]['title']);

        $response = $this->get('/api/offices');

        $response->assertOk();
        $this->assertEquals('Leiria', $response->json('data')[0]['title']);
        $this->assertEquals('Torres Vedras', $response->json('data')[1]['title']);
    }

    /**
    * @test
    */
    public function ItsShowTheOffice()
    {
        $user = User::factory()->create();
        
        $tag = Tag::factory()->create();

        $office = Office::factory()->for($user)->create();
        
        $office->tags()->attach($tag);

        $office->images()->create(['path' => 'image.png']);

        Reservation::factory()->for($office)->create(['status' => Reservation::STATUS_ACTIVE]);
        Reservation::factory()->for($office)->create(['status' => Reservation::STATUS_CANCELLED]);
        $response = $this->get('/api/offices/'.$office->id);
        
        $this->assertEquals(1, $response->json('data')['reservations_count']);
        $this->assertIsArray($response->json('data')['tags']);
        $this->assertCount(1, $response->json('data')['tags']);
        $this->assertIsArray($response->json('data')['images']);
        $this->assertCount(1, $response->json('data')['images']);
        $this->assertEquals($user->id, $response->json('data')['user']['id']);
    }
}
