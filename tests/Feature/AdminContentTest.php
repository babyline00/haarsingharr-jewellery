<?php

use App\Mail\EnquiryReplyMail;
use App\Mail\TemplateTestMail;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\CmsPage;
use App\Models\CmsPageRevision;
use App\Models\ContactEnquiry;
use App\Models\EmailTemplate;
use App\Models\NavigationMenu;
use App\Models\NewsletterSubscription;
use Illuminate\Support\Facades\Mail;

/*
|--------------------------------------------------------------------------
| Admin content & comms tests (Phase 5)
|--------------------------------------------------------------------------
| Blog, navigation menus, email templates, enquiries inbox, newsletter
| subscribers and CMS revisions/preview.
*/

beforeEach(function () {
    $this->actingAs(createAdmin());
});

// --------------------------------------------------------------------------
// Blog
// --------------------------------------------------------------------------

it('creates blog posts with tags that resolve case-insensitively', function () {
    BlogTag::create(['name' => 'styling', 'slug' => 'styling']);

    $this->post(route('admin.blog.store'), [
        'title'    => 'Layering 101',
        'content'  => "## Intro\n\nLayer gold with silver.",
        'status'   => 'draft',
        'tags'     => 'STYLING, bridal',
    ])->assertRedirect();

    $post = BlogPost::where('title', 'Layering 101')->first();
    $tagNames = $post->tags->pluck('name')->sort()->values();

    expect($post)->not->toBeNull()
        ->and($post->slug)->toBe('layering-101')
        ->and($post->author_id)->toBe(auth()->id())
        ->and(BlogTag::count())->toBe(2) // no duplicate "styling" tag
        ->and($tagNames->all())->toBe(['bridal', 'styling']);
});

it('turns a publish-with-future-date into a scheduled post and hides it from the storefront', function () {
    $post = BlogPost::create([
        'title'        => 'Eid campaign',
        'slug'         => 'eid-campaign',
        'content'      => 'Story text.',
        'status'       => 'draft',
        'published_at' => now()->addDays(3),
    ]);

    $this->put(route('admin.blog.update', $post), [
        'title' => 'Eid campaign', 'status' => 'published', 'published_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
    ])->assertRedirect();

    expect($post->fresh()->status)->toBe('scheduled');

    $this->post(route('admin.blog.bulk'), ['ids' => [$post->id], 'action' => 'draft'])->assertRedirect();
    expect($post->fresh()->status)->toBe('draft');

    BlogPost::create([
        'title' => 'Live story', 'slug' => 'live-story', 'content' => 'x', 'status' => 'published',
    ]);

    $this->get(route('blog.index'))->assertOk()->assertSee('Live story')->assertDontSee('Eid campaign');
    $this->get(route('blog.show', ['post' => 'eid-campaign']))->assertNotFound();

    // Staff preview renders the unpublished post.
    $this->get(route('admin.blog.preview', $post))->assertOk()->assertSee('Eid campaign');
});

// --------------------------------------------------------------------------
// Navigation menus
// --------------------------------------------------------------------------

it('stores and renders navigation menus on the storefront', function () {
    $page = CmsPage::create(['title' => 'Care guide', 'slug' => 'care-guide', 'template' => 'default', 'status' => 'published']);
    $category = \App\Models\Category::create([
        'name'      => 'Rings',
        'slug'      => 'rings',
        'show_in_menu' => true,
    ]);

    $items = [
        ['label' => 'All Jewellery', 'type' => 'custom', 'link' => '/shop', 'is_visible' => true, 'children' => []],
        ['label' => 'Rings', 'type' => 'category', 'link' => (string) $category->id, 'is_visible' => true, 'children' => [
            ['label' => 'Care guide', 'type' => 'page', 'link' => 'care-guide', 'is_visible' => true],
            ['label' => 'Hidden child', 'type' => 'custom', 'link' => '/x', 'is_visible' => false],
        ]],
        ['label' => 'Off', 'type' => 'custom', 'link' => '/off', 'is_visible' => false, 'children' => []],
    ];

    $this->post(route('admin.menus.store'), [
        'name'     => 'Header main',
        'location' => 'main',
        'items'    => json_encode($items),
    ])->assertRedirect();

    $menu = NavigationMenu::where('name', 'Header main')->first();
    expect(count($menu->items))->toBe(3)
        ->and(count($menu->items[1]['children']))->toBe(2);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('All Jewellery')
        ->assertSee('/categories/'.$category->slug, false)
        ->assertSee('/pages/care-guide', false)
        ->assertDontSee('>Off<');

    $visible = collect($menu->visibleItems());
    expect($visible->count())->toBe(2) // hidden item filtered
        ->and($visible->pluck('label'))->toContain('Rings')
        ->and(collect($visible[1]['children'])->count())->toBe(1) // invisible child filtered
        ->and(\App\Models\NavigationMenu::resolveUrl(['type' => 'page', 'link' => $page->slug]))->toContain('/pages/care-guide');
});

// --------------------------------------------------------------------------
// Email templates
// --------------------------------------------------------------------------

it('edits email templates with variables, preview and test send', function () {
    Mail::fake();

    $template = EmailTemplate::create([
        'key'       => 'order_delivered',
        'name'      => 'Order delivered',
        'subject'   => 'Your order {{order_number}} has arrived',
        'body'      => '<p>Hi {{customer_name}}, enjoy your jewellery!</p>',
        'variables' => ['order_number', 'customer_name'],
        'is_active' => true,
    ]);

    $this->get(route('admin.emails.edit', $template))->assertOk()->assertSee('order_delivered', false);
    $this->get(route('admin.emails.preview', $template))
        ->assertOk()
        ->assertSee('HS-26-000123');

    $this->put(route('admin.emails.update', $template), [
        'subject'   => 'Order {{order_number}} is complete!',
        'body'      => '<p>Hi {{customer_name}} — {{tracking_number}}</p>',
        'variables' => 'order_number, customer_name, tracking_number',
        'is_active' => '1',
    ])->assertRedirect();

    $template->refresh();
    expect($template->subject)->toBe('Order {{order_number}} is complete!')
        ->and($template->variables)->toBe(['order_number', 'customer_name', 'tracking_number']);

    $this->post(route('admin.emails.test-send', $template))->assertRedirect();

    Mail::assertSent(TemplateTestMail::class, fn ($mail) => $mail->hasTo(auth()->user()->email)
        && $mail->renderedSubject === '[TEST] Order HS-26-000123 is complete!');

    $this->post(route('admin.emails.toggle', $template))->assertRedirect();
    expect($template->fresh()->is_active)->toBeFalse();
});

// --------------------------------------------------------------------------
// Enquiries
// --------------------------------------------------------------------------

it('runs the enquiry inbox: assign, reply email and bulk close', function () {
    Mail::fake();

    $mine = ContactEnquiry::create([
        'name' => 'Zainab Ali', 'email' => 'zainab@example.com', 'subject' => 'Ring size?',
        'message' => 'What size should I order?', 'type' => 'support', 'status' => 'new',
    ]);

    $other = ContactEnquiry::create([
        'name' => 'Kiran Shah', 'email' => 'kiran@example.com', 'subject' => 'Wholesale',
        'message' => 'B2B pricing please.', 'type' => 'wholesale', 'status' => 'resolved',
    ]);

    // Default inbox only shows unresolved.
    $this->get(route('admin.enquiries.index'))
        ->assertOk()->assertSee('Zainab Ali')->assertDontSee('Kiran Shah');
    $this->get(route('admin.enquiries.index', ['status' => 'resolved']))
        ->assertOk()->assertSee('Kiran Shah');

    $this->post(route('admin.enquiries.assign', $mine), ['assigned_to' => auth()->id()])->assertRedirect();
    expect($mine->fresh()->assigned_to)->toBe(auth()->id())
        ->and($mine->fresh()->status)->toBe('open'); // claiming takes it out of "new"

    $this->post(route('admin.enquiries.reply', $mine), [
        'message' => 'Our size guide recommends a 16mm internal diameter.',
        'resolve' => '1',
    ])->assertRedirect();

    Mail::assertSent(EnquiryReplyMail::class, fn ($mail) => $mail->hasTo('zainab@example.com'));

    expect($mine->fresh()->status)->toBe('resolved')
        ->and($mine->fresh()->internal_notes)->toContain('replied');

    $this->get(route('admin.enquiries.export'))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $this->post(route('admin.enquiries.bulk'), ['ids' => [$other->id], 'action' => 'close'])->assertRedirect();
    expect($other->fresh()->status)->toBe('closed');
});

// --------------------------------------------------------------------------
// Newsletter
// --------------------------------------------------------------------------

it('exports and bulk-manages newsletter subscribers', function () {
    $one = NewsletterSubscription::subscribe('a@example.com');
    $two = NewsletterSubscription::subscribe('b@example.com');

    $this->get(route('admin.newsletter.index'))->assertOk()->assertSee('a@example.com');

    $this->post(route('admin.newsletter.bulk'), [
        'ids' => [$one->id], 'action' => 'unsubscribe',
    ])->assertRedirect();

    expect($one->fresh()->status)->toBe('unsubscribed')
        ->and($one->fresh()->unsubscribed_at)->not->toBeNull();

    $this->post(route('admin.newsletter.bulk'), [
        'ids' => [$one->id], 'action' => 'subscribe',
    ])->assertRedirect();

    expect($one->fresh()->status)->toBe('subscribed')
        ->and($one->fresh()->unsubscribed_at)->toBeNull();

    $csv = $this->get(route('admin.newsletter.export', ['ids' => $one->id.','.$two->id]))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

// --------------------------------------------------------------------------
// CMS revisions & preview
// --------------------------------------------------------------------------

it('snapshots cms revisions on update and restores a previous one', function () {
    $page = CmsPage::create([
        'title' => 'Shipping', 'slug' => 'shipping-policy', 'template' => 'legal',
        'content' => 'Original text.', 'status' => 'published',
        'created_by' => auth()->id(), 'updated_by' => auth()->id(),
    ]);
    // First revision exists after a create-passing update; restore earlier flow Tested below.
    $this->put(route('admin.pages.update', $page), [
        'title' => 'Shipping', 'slug' => 'shipping-policy', 'template' => 'legal',
        'content' => 'Rewritten with new courier partners.', 'status' => 'published',
        'seo_title' => 'Shipping — H', 'seo_description' => 'desc',
    ])->assertRedirect();

    $page->refresh();
    expect($page->revisions()->count())->toBe(1)
        ->and($page->revisions()->latest('id')->first()->content)->toBe('Original text.');

    $revision = $page->revisions()->latest('id')->first();

    $this->post(route('admin.pages.revisions.restore', [$page, $revision]))->assertRedirect();
    $page->refresh();

    expect($page->content)->toBe('Original text.')
        // The rollback itself was snapshotted first — reversible.
        ->and($page->revisions()->count())->toBe(2)
        ->and($page->revisions()->latest('id')->first()->content)->toBe('Rewritten with new courier partners.');

    // Staff preview renders drafts too (non-published content).
    $draft = CmsPage::create([
        'title' => 'Secret page', 'slug' => 'secret-page', 'template' => 'default',
        'content' => 'Hush draft.', 'status' => 'draft',
    ]);
    $this->get(route('admin.pages.preview', $draft))->assertOk()->assertSee('Hush draft');
});
