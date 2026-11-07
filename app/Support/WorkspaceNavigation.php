<?php

namespace App\Support;

use Illuminate\Http\Request;

final class WorkspaceNavigation
{
    /**
     * A single navigation registry shared across all account workspaces.
     * Every entry targets an implemented route; there are no placeholder URLs.
     *
     * @return list<array{label:string,icon:string,url:string,active:bool}>
     */
    public static function for(string $workspace, Request $request): array
    {
        $definitions = match ($workspace) {
            'admin' => [
                ['Overview', 'grid', 'admin.home', [], 'admin.home'],
                ['Category Manager', 'tag', 'admin.categories', [], 'admin.categories*'],
                ['Vendors', 'store', 'admin.vendors', [], 'admin.vendors*'],
                ['Orders', 'orders', 'admin.orders', [], 'admin.orders*'],
                ['Media Library', 'box', 'admin.media', [], 'admin.media*'],
                ['Page Manager', 'edit', 'admin.content.index', ['type' => 'page'], 'admin.content.*'],
                ['Post Manager', 'edit', 'admin.content.index', ['type' => 'post'], 'admin.content.*'],
                ['Policy Center', 'shield', 'admin.policies.index', [], 'admin.policies.*'],
                ['Knowledge Base', 'orders', 'admin.knowledge.index', [], 'admin.knowledge.*'],
                ['Support Center', 'mail', 'admin.support.index', [], 'admin.support.*'],
                ['Appearance', 'sun', 'admin.appearance', [], 'admin.appearance*'],
                ['General Settings', 'edit', 'admin.settings', [], 'admin.settings*'],
                ['System Health', 'shield', 'admin.health', [], 'admin.health'],
            ],
            'vendor' => [
                ['Overview', 'grid', 'vendor.home', [], 'vendor.home'],
                ['My Products', 'box', 'vendor.products', [], 'vendor.products*'],
                ['Store Orders', 'orders', 'vendor.orders', [], 'vendor.orders*'],
                ['Delivery Settings', 'store', 'vendor.shipping.edit', [], 'vendor.shipping.*'],
                ['Media Library', 'box', 'vendor.media', [], 'vendor.media*'],
            ],
            default => [
                ['Overview', 'grid', 'account.home', [], 'account.home'],
                ['My Orders', 'orders', 'account.orders', [], 'account.orders'],
                ['Support Tickets', 'mail', 'account.support.index', [], 'account.support.*'],
            ],
        };

        return array_map(static function (array $item) use ($request): array {
            [$label, $icon, $route, $parameters, $activePattern] = $item;

            $active = $request->routeIs($activePattern);
            if ($route === 'admin.content.index') {
                $active = $active && $request->route('type') === $parameters['type'];
            }

            return [
                'label' => $label,
                'icon' => $icon,
                'url' => route($route, $parameters),
                'active' => $active,
            ];
        }, $definitions);
    }
}
