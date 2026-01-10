<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Post;
use App\Models\Employee;
use App\Models\Testimonial;
use App\Models\Partner;
use App\Models\Menu;
use App\Models\Setting;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function pages(Request $request)
    {
        $lang = $request->header('Accept-Language', 'uk');
        
        $pages = Page::published()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($page) use ($lang) {
                return [
                    'id' => $page->id,
                    'slug' => $page->slug,
                    'title' => $page->name[$lang] ?? array_values($page->name)[0] ?? '',
                    'excerpt' => mb_substr(strip_tags($page->content[$lang] ?? array_values($page->content)[0] ?? ''), 0, 150),
                    'meta' => [
                        'title' => $page->seo['meta_title'][$lang] ?? $page->seo['meta_title']['uk'] ?? '',
                        'description' => $page->seo['meta_description'][$lang] ?? $page->seo['meta_description']['uk'] ?? '',
                    ],
                    'created_at' => $page->created_at,
                ];
            });
            
        return response()->json([
            'success' => true,
            'data' => $pages,
        ]);
    }

    public function page($slug, Request $request)
    {
        $lang = $request->header('Accept-Language', 'uk');
        
        $page = Page::where('slug', $slug)
            ->published()
            ->first();
            
        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $page->id,
                'slug' => $page->slug,
                'title' => $page->name[$lang] ?? array_values($page->name)[0] ?? '',
                'content' => $page->content[$lang] ?? array_values($page->content)[0] ?? '',
                'meta' => [
                    'title' => $page->seo['meta_title'][$lang] ?? $page->seo['meta_title']['uk'] ?? '',
                    'description' => $page->seo['meta_description'][$lang] ?? $page->seo['meta_description']['uk'] ?? '',
                    'keywords' => $page->seo['meta_keywords'][$lang] ?? $page->seo['meta_keywords']['uk'] ?? '',
                ],
                'created_at' => $page->created_at,
            ],
        ]);
    }

    public function posts(Request $request)
    {
        $lang = $request->header('Accept-Language', 'uk');
        
        $posts = Post::published()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($post) use ($lang) {
                return [
                    'id' => $post->id,
                    'slug' => $post->slug,
                    'title' => $post->name[$lang] ?? array_values($post->name)[0] ?? '',
                    'excerpt' => $post->description[$lang] ?? array_values($post->description)[0] ?? '',
                    'author' => $post->author[$lang] ?? array_values($post->author)[0] ?? '',
                    'image' => $post->image,
                    'meta' => [
                        'title' => $post->seo['meta_title'][$lang] ?? $post->seo['meta_title']['uk'] ?? '',
                        'description' => $post->seo['meta_description'][$lang] ?? $post->seo['meta_description']['uk'] ?? '',
                    ],
                    'created_at' => $post->created_at,
                ];
            });
            
        return response()->json([
            'success' => true,
            'data' => $posts,
        ]);
    }

    public function post($slug, Request $request)
    {
        $lang = $request->header('Accept-Language', 'uk');
        
        $post = Post::where('slug', $slug)
            ->published()
            ->with('user', 'tags')
            ->first();
            
        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $post->id,
                'slug' => $post->slug,
                'title' => $post->name[$lang] ?? array_values($post->name)[0] ?? '',
                'content' => $post->content[$lang] ?? array_values($post.content)[0] ?? '',
                'author' => $post->author[$lang] ?? array_values($post->author)[0] ?? '',
                'image' => $post->image,
                'meta' => [
                    'title' => $post->seo['meta_title'][$lang] ?? $post->seo['meta_title']['uk'] ?? '',
                    'description' => $post->seo['meta_description'][$lang] ?? $post->seo['meta_description']['uk'] ?? '',
                ],
                'tags' => $post->tags->map(function ($tag) use ($lang) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name[$lang] ?? array_values($tag->name)[0] ?? '',
                        'slug' => $tag->slug,
                    ];
                }),
                'created_at' => $post->created_at,
            ],
        ]);
    }

    public function employees(Request $request)
    {
        $lang = $request->header('Accept-Language', 'uk');
        
        $employees = Employee::published()
            ->orderBy('order')
            ->get()
            ->map(function ($employee) use ($lang) {
                return [
                    'id' => $employee->id,
                    'name' => $employee->name[$lang] ?? array_values($employee->name)[0] ?? '',
                    'position' => $employee->position,
                    'description' => $employee->description[$lang] ?? array_values($employee->description)[0] ?? '',
                    'details' => $employee->details[$lang] ?? $employee->details,
                    'image' => $employee->image,
                    'order' => $employee->order,
                ];
            });
            
        return response()->json([
            'success' => true,
            'data' => $employees,
        ]);
    }

    public function testimonials(Request $request)
    {
        $lang = $request->header('Accept-Language', 'uk');
        
        $testimonials = Testimonial::published()
            ->orderBy('order')
            ->get()
            ->map(function ($testimonial) use ($lang) {
                return [
                    'id' => $testimonial->id,
                    'type' => $testimonial->type,
                    'description' => $testimonial->description[$lang] ?? array_values($testimonial->description)[0] ?? '',
                    'text' => $testimonial->text,
                    'image' => $testimonial->image,
                    'video' => $testimonial->video,
                    'order' => $testimonial->order,
                ];
            });
            
        return response()->json([
            'success' => true,
            'data' => $testimonials,
        ]);
    }

    public function partners(Request $request)
    {
        $lang = $request->header('Accept-Language', 'uk');
        
        $partners = Partner::published()
            ->orderBy('order')
            ->get()
            ->map(function ($partner) use ($lang) {
                return [
                    'id' => $partner->id,
                    'description' => $partner->description[$lang] ?? array_values($partner->description)[0] ?? '',
                    'image' => $partner->image,
                    'order' => $partner->order,
                ];
            });
            
        return response()->json([
            'success' => true,
            'data' => $partners,
        ]);
    }

    // Добавляем метод для меню
    public function menus(Request $request)
    {
        $lang = $request->header('Accept-Language', 'uk');
        
        $menus = Menu::published()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($menu) use ($lang) {
                return [
                    'id' => $menu->id,
                    'layout' => $menu->layout,
                    'name' => $menu->name[$lang] ?? array_values($menu->name)[0] ?? '',
                    'properties' => $menu->properties,
                    'visibility' => $menu->visibility,
                    'created_at' => $menu->created_at,
                ];
            });
            
        return response()->json([
            'success' => true,
            'data' => $menus,
        ]);
    }

    // Добавляем метод для настроек
    public function settings($group, Request $request)
    {
        $lang = $request->header('Accept-Language', 'uk');
        
        $settings = Setting::where('group', $group)
            ->get()
            ->mapWithKeys(function ($setting) use ($lang) {
                $value = $setting->value;
                
                // Если значение - массив (мультиязычное), берем нужный язык
                if (is_array($value)) {
                    $value = $value[$lang] ?? $value['uk'] ?? array_values($value)[0] ?? null;
                }
                
                return [$setting->name => $value];
            });
            
        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    // Дополнительный метод для получения всех настроек
    public function allSettings(Request $request)
    {
        $lang = $request->header('Accept-Language', 'uk');
        
        $settings = Setting::all()
            ->groupBy('group')
            ->map(function ($groupSettings) use ($lang) {
                return $groupSettings->mapWithKeys(function ($setting) use ($lang) {
                    $value = $setting->value;
                    
                    if (is_array($value)) {
                        $value = $value[$lang] ?? $value['uk'] ?? array_values($value)[0] ?? null;
                    }
                    
                    return [$setting->name => $value];
                });
            });
            
        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }
}