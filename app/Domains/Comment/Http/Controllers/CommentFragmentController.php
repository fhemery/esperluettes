<?php

namespace App\Domains\Comment\Http\Controllers;

use App\Domains\Comment\PublicApi\CommentPublicApi;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Http\Response;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\UnauthorizedException;

class CommentFragmentController extends Controller
{
    public function __construct(private CommentPublicApi $api)
    {
    }

    public function items(Request $request): Response
    {
        $data = $request->validate([
            'entity_type' => ['required','string'],
            'entity_id' => ['required','integer'],
            'page' => ['sometimes','integer','min:1'],
            'per_page' => ['sometimes','integer','min:1','max:100'],
        ]);

        $page = (int) ($data['page'] ?? 1);
        $perPage = (int) ($data['per_page'] ?? 20);

        try {
            $list = $this->api->getFor($data['entity_type'], (int) $data['entity_id'], $page, $perPage);
        } catch (UnauthorizedException $e) {
            throw new HttpResponseException(response('', 401));
        }

        $total = $list->total;
        $lastPage = (int) max(1, (int) ceil($total / max(1, $list->perPage)));
        $nextPage = $page < $lastPage ? $page + 1 : null;

        $html = view('comment::fragments.items', [
            'items' => $list->items,
        ])->render();

        $response = new Response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
        if ($nextPage) {
            $response->headers->set('X-Next-Page', (string) $nextPage);
        }
        return $response;
    }
}
