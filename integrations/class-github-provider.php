<?php

if (!defined('ABSPATH')) {
    exit;
}

final class BastionWP_GitHub_Provider
{
    private string $owner;
    private string $repository;
    private string $channel;

    public function __construct(string $owner, string $repository, string $channel = 'stable')
    {
        $this->owner = sanitize_text_field($owner);
        $this->repository = sanitize_text_field($repository);
        $this->channel = in_array($channel, ['stable', 'beta'], true) ? $channel : 'stable';
    }

    public function is_configured(): bool
    {
        return $this->owner !== '' && $this->repository !== '';
    }

    public function get_latest_release(bool $force = false)
    {
        if (!$this->is_configured()) {
            return new WP_Error(
                'bastionwp_github_not_configured',
                __('Informe o proprietário e o repositório do GitHub para ativar as atualizações.', 'bastionwp')
            );
        }

        $cache_key = $this->cache_key();

        if (!$force) {
            $cached = get_site_transient($cache_key);

            if (is_array($cached) && !empty($cached['version'])) {
                return $cached;
            }
        }

        $result = $this->channel === 'beta'
            ? $this->fetch_release_list()
            : $this->fetch_latest_stable_release();

        if (is_wp_error($result)) {
            return $result;
        }

        set_site_transient($cache_key, $result, 6 * HOUR_IN_SECONDS);

        return $result;
    }

    public function clear_cache(): void
    {
        delete_site_transient($this->cache_key());
    }

    private function fetch_latest_stable_release()
    {
        $url = sprintf(
            'https://api.github.com/repos/%s/%s/releases/latest',
            rawurlencode($this->owner),
            rawurlencode($this->repository)
        );

        $response = $this->request($url);

        if (is_wp_error($response)) {
            return $response;
        }

        return $this->normalize_release($response);
    }

    private function fetch_release_list()
    {
        $url = sprintf(
            'https://api.github.com/repos/%s/%s/releases?per_page=30',
            rawurlencode($this->owner),
            rawurlencode($this->repository)
        );

        $response = $this->request($url);

        if (is_wp_error($response)) {
            return $response;
        }

        if (!is_array($response)) {
            return new WP_Error(
                'bastionwp_github_invalid_release_list',
                __('O GitHub retornou uma lista de versões inválida.', 'bastionwp')
            );
        }

        $best = null;

        foreach ($response as $release) {
            if (!is_array($release) || !empty($release['draft'])) {
                continue;
            }

            $normalized = $this->normalize_release($release);

            if (is_wp_error($normalized)) {
                continue;
            }

            if ($best === null || version_compare($normalized['version'], $best['version'], '>')) {
                $best = $normalized;
            }
        }

        if ($best === null) {
            return new WP_Error(
                'bastionwp_github_no_release',
                __('Nenhuma versão publicável do BastionWP foi encontrada no GitHub.', 'bastionwp')
            );
        }

        return $best;
    }

    private function request(string $url)
    {
        $response = wp_remote_get(
            $url,
            [
                'timeout' => 12,
                'headers' => [
                    'Accept'     => 'application/vnd.github+json',
                    'User-Agent' => 'BastionWP/' . BASTIONWP_VERSION,
                ],
            ]
        );

        if (is_wp_error($response)) {
            return $response;
        }

        $status = (int) wp_remote_retrieve_response_code($response);

        if ($status !== 200) {
            if ($status === 404) {
                return new WP_Error(
                    'bastionwp_github_not_found',
                    __('Repositório ou Release não encontrado. Confirme se o repositório é público e se existe uma Release publicada.', 'bastionwp')
                );
            }

            if ($status === 403) {
                return new WP_Error(
                    'bastionwp_github_forbidden',
                    __('O GitHub recusou temporariamente a consulta. Aguarde e tente novamente ou verifique os limites da API.', 'bastionwp')
                );
            }

            return new WP_Error(
                'bastionwp_github_http_error',
                sprintf(
                    __('O GitHub respondeu com o código HTTP %d.', 'bastionwp'),
                    $status
                )
            );
        }

        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error(
                'bastionwp_github_json_error',
                __('Não foi possível interpretar a resposta de atualização do GitHub.', 'bastionwp')
            );
        }

        return $decoded;
    }

    private function normalize_release(array $release)
    {
        if (!empty($release['draft'])) {
            return new WP_Error(
                'bastionwp_github_draft',
                __('A versão encontrada ainda é um rascunho.', 'bastionwp')
            );
        }

        if ($this->channel === 'stable' && !empty($release['prerelease'])) {
            return new WP_Error(
                'bastionwp_github_prerelease',
                __('A versão encontrada é beta/pré-lançamento e o canal atual é Estável.', 'bastionwp')
            );
        }

        $tag = isset($release['tag_name']) ? trim((string) $release['tag_name']) : '';
        $version = ltrim($tag, "vV");

        if (
            $version === ''
            || !preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version)
        ) {
            return new WP_Error(
                'bastionwp_github_missing_version',
                __('A Release do GitHub não possui uma tag de versão válida.', 'bastionwp')
            );
        }

        $package = $this->find_package_asset($release, $version);

        if ($package === '') {
            return new WP_Error(
                'bastionwp_github_missing_asset',
                sprintf(
                    __('A Release %s existe, mas não contém o arquivo ZIP instalável do BastionWP.', 'bastionwp'),
                    $tag
                )
            );
        }

        $body = isset($release['body']) ? (string) $release['body'] : '';
        $requires_php = $this->extract_requirement($body, 'Requires PHP');
        $requires_wp = $this->extract_requirement($body, 'Requires at least');

        return [
            'version'      => $version,
            'tag'          => $tag,
            'package'      => esc_url_raw($package),
            'url'          => isset($release['html_url']) ? esc_url_raw((string) $release['html_url']) : '',
            'published_at' => isset($release['published_at']) ? sanitize_text_field((string) $release['published_at']) : '',
            'prerelease'   => !empty($release['prerelease']),
            'requires_php' => $requires_php,
            'requires_wp'  => $requires_wp,
        ];
    }

    private function find_package_asset(array $release, string $version): string
    {
        $assets = isset($release['assets']) && is_array($release['assets'])
            ? $release['assets']
            : [];

        $preferred = [
            'bastionwp-' . $version . '.zip',
            'bastionwp-v' . $version . '.zip',
            'bastionwp.zip',
        ];

        $matches = [];

        foreach ($assets as $asset) {
            if (!isset($asset['name'], $asset['browser_download_url'])) {
                continue;
            }

            $name = (string) $asset['name'];
            $lower = strtolower($name);

            if (
                str_contains($lower, 'source')
                || str_contains($lower, 'backup')
                || str_contains($lower, 'src')
            ) {
                continue;
            }

            if (in_array($name, $preferred, true)) {
                $matches[$name] = (string) $asset['browser_download_url'];
            }
        }

        foreach ($preferred as $filename) {
            if (isset($matches[$filename])) {
                return $matches[$filename];
            }
        }

        return '';
    }

    private function extract_requirement(string $body, string $label): string
    {
        if (
            preg_match(
                '/^\s*' . preg_quote($label, '/') . '\s*:\s*([0-9]+(?:\.[0-9]+){0,2})\s*$/mi',
                $body,
                $matches
            )
        ) {
            return sanitize_text_field((string) $matches[1]);
        }

        return '';
    }

    private function cache_key(): string
    {
        return 'bastionwp_gh_' . substr(
            hash('sha256', strtolower($this->owner . '/' . $this->repository . '/' . $this->channel)),
            0,
            24
        );
    }
}
