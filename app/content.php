<?php
// containers-docs — single source of truth for site meta and the docs nav tree.
// Each page maps to a markdown file in content/<slug>.md.

return [
    'site' => [
        'name'        => 'Containers Docs',
        'shortName'   => 'Containers',
        'description' => 'Documentation for the containers collection: production-ready Docker images built with forge, supervised by ezx, and configured entirely through environment variables.',
        'baseUrl'     => 'https://docs.container.supanadit.com',
        'landingUrl'  => 'https://containers.supanadit.com',
        'repoUrl'     => 'https://github.com/supanadit/containers',
        'registry'    => 'ghcr.io/supanadit/containers',
        'author'      => 'Supan Adit Pratama',
        'copyright'   => "\xC2\xA9 2026 Supan Adit Pratama",
    ],

    // Default page when /docs is requested.
    'root' => 'why',

    'nav' => [
        [
            'title' => 'Getting started',
            'pages' => [
                ['slug' => 'why',           'title' => 'Why containers'],
                ['slug' => 'quickstart',    'title' => 'Quickstart'],
                ['slug' => 'configuration', 'title' => 'Configuration'],
                ['slug' => 'health',        'title' => 'Health & readiness'],
                ['slug' => 'volumes',       'title' => 'Volumes & backups'],
            ],
        ],
        [
            'title' => 'Databases',
            'pages' => [
                ['slug' => 'postgresql', 'title' => 'PostgreSQL'],
                ['slug' => 'mariadb',    'title' => 'MariaDB'],
                ['slug' => 'pgpool-ii',  'title' => 'Pgpool-II'],
                ['slug' => 'maxscale',   'title' => 'MaxScale'],
            ],
        ],
        [
            'title' => 'Messaging & coordination',
            'pages' => [
                ['slug' => 'apache-kafka',     'title' => 'Apache Kafka'],
                ['slug' => 'apache-zookeeper', 'title' => 'Apache ZooKeeper'],
                ['slug' => 'etcd',             'title' => 'etcd'],
            ],
        ],
        [
            'title' => 'Observability',
            'pages' => [
                ['slug' => 'prometheus', 'title' => 'Prometheus'],
                ['slug' => 'grafana',    'title' => 'Grafana'],
                ['slug' => 'loki',       'title' => 'Grafana Loki'],
                ['slug' => 'mimir',      'title' => 'Grafana Mimir'],
                ['slug' => 'thanos',     'title' => 'Thanos'],
            ],
        ],
        [
            'title' => 'Applications',
            'pages' => [
                ['slug' => 'wordpress', 'title' => 'WordPress'],
                ['slug' => 'minio',     'title' => 'MinIO'],
            ],
        ],
        [
            'title' => 'Reference',
            'pages' => [
                ['slug' => 'troubleshooting', 'title' => 'Troubleshooting'],
            ],
        ],
    ],
];

/** Flatten the nav tree to an ordered list of pages (with their group titles). */
function docs_pages(array $C): array
{
    $out = [];
    foreach ($C['nav'] as $group) {
        foreach ($group['pages'] as $p) {
            $p['group'] = $group['title'];
            $out[] = $p;
        }
    }
    return $out;
}

/** Find one page by slug, or null. */
function docs_find(array $C, string $slug): ?array
{
    foreach (docs_pages($C) as $p) {
        if ($p['slug'] === $slug) {
            return $p;
        }
    }
    return null;
}

/** Filesystem path of a page's markdown source. */
function docs_path(array $C, string $slug): string
{
    return dirname(__DIR__) . '/content/' . $slug . '.md';
}
