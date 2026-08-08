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
