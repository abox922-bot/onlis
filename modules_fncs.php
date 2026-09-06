<?php

function fncFlattenGroupOptions($nodes, $level, $forbidden_ids, &$options) {
    foreach ($nodes as $node) {
        if (in_array((int)$node['id'], $forbidden_ids, true)) {
            continue;
        }
        $options[] = [
            'id'    => $node['id'],
            'label' => str_repeat('— ', $level) . $node['name'],
        ];
        if (!empty($node['children'])) {
            fncFlattenGroupOptions($node['children'], $level + 1, $forbidden_ids, $options);
        }
    }
}

function fncCollectForbiddenIds($nodes, $exclude_id, $found = false) {
    $ids = [];
    foreach ($nodes as $node) {
        $is_excluded_branch = $found || ((int)$node['id'] === (int)$exclude_id);
        if ($is_excluded_branch) {
            $ids[] = (int)$node['id'];
        }
        if (!empty($node['children'])) {
            $ids = array_merge($ids, fncCollectForbiddenIds($node['children'], $exclude_id, $is_excluded_branch));
        }
    }
    return $ids;
}

function fncFormatNumber($value, $decimals = 2) {
    if ($value === null || $value === '') {
        return '';
    }
    return number_format((float)$value, $decimals, ',', ' ');
}

// Проверка видимости UI-элемента (пункт меню, раздел, вкладка), когда право
// распределено по параллельным дочерним slug (per-type), а не по линейной
// вложенности — см. модуль Товароучёт, документы разных типов.
// ВНИМАНИЕ: только для видимости интерфейса. НИКОГДА не использовать для
// защиты реального действия (создание/проведение/удаление) — возвращает
// true при наличии ЛЮБОГО дочернего права по префиксу, не именно требуемого.
// Для защиты действия всегда использовать fncCan() с точным slug.
function fncCanAny(array $perms, string $prefix): bool {
    $parent = rtrim($prefix, '.');
    if (fncCan($perms, $parent)) {
        return true;
    }
    foreach ($perms as $slug) {
        if (strpos($slug, $prefix) === 0) {
            return true;
        }
    }
    return false;
}

// Единая проверка видимости пункта меню/раздела: поддерживает как обычный
// 'slug' (проверка через fncCan с подъёмом по иерархии), так и 'slug_prefix'
// (проверка через fncCanAny — для случаев per-type прав).
function fncMenuItemVisible(array $perms, array $item): bool {
    if (isset($item['slug_prefix'])) {
        return fncCanAny($perms, $item['slug_prefix']);
    }
    return !isset($item['slug']) || fncCan($perms, $item['slug']);
}
