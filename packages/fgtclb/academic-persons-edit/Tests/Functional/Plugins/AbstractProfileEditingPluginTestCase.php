<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Tests\Functional\Plugins;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * Test harness retained exclusively for the legacy ProfileEditing reference code.
 *
 * New tests must extend AbstractFrontendProfilePluginTestCase directly and provide an
 * InlineProfile fixture instead of inheriting this legacy action and fixture contract.
 */
abstract class AbstractProfileEditingPluginTestCase extends AbstractFrontendProfilePluginTestCase
{
    protected function setUpTestCase(): void
    {
        $this->setUpFrontendProfileTestCase(
            __DIR__ . '/Fixtures/AcademicPersonsEditProfilePlugin/profileEditingPage.csv',
            'ProfileEditing',
        );
    }

    /**
     * Returns the absolute URI of the link the given legacy Extbase action is rendered with.
     */
    protected function extractActionLink(string $content, string $action): string
    {
        preg_match_all('@href="([^"]+)"@', $content, $matches);
        foreach ($matches[1] as $href) {
            $href = html_entity_decode($href);
            if (
                !str_contains($href, urlencode('[action]') . '=' . $action)
                && !str_contains($href, '[action]=' . $action)
            ) {
                continue;
            }
            return str_starts_with($href, '/') ? 'https://www.acme.com' . $href : $href;
        }
        $this->fail(sprintf('No link to action "%s" found in the rendered page.', $action));
    }

    /**
     * Walks the plugin from the profile list to the profile edit form and returns its URI.
     *
     * The inherited `extractActionLink()` cannot be used: it matches an action name by prefix,
     * so `edit` returns the `editImage` link that is rendered above it.
     */
    protected function getProfileEditFormUrl(): string
    {
        preg_match_all('@href="([^"]+)"@', $this->getProfileShowPage(), $matches);
        foreach ($matches[1] as $href) {
            $href = html_entity_decode($href);
            if (!str_contains($href, urlencode('[action]') . '=edit&')) {
                continue;
            }
            return str_starts_with($href, '/') ? 'https://www.acme.com' . $href : $href;
        }
        $this->fail('No link to the "edit" action found on the profile show page.');
    }

    /**
     * Renders the edit page and returns the update form's action URI together with its hidden
     * fields. The action carries controller and action, the hidden fields carry `__referrer`
     * and the `__trustedProperties` HMAC, so neither can be hardcoded.
     *
     * The page renders more than one form, therefore the update form is selected by its action.
     *
     * @return array{action: string, fields: array<string, string>}
     */
    protected function renderEditFormAndExtractSubmitData(string $formUrl): array
    {
        $content = $this->getPageAsFrontendUser($formUrl);
        $this->assertSame(
            1,
            preg_match(
                '@<form [^>]*action="([^"]*' . urlencode('[action]') . '=update[^"]*)"(.*?)</form>@s',
                $content,
                $formMatch,
            ),
            'The profile edit page does not contain a form posting to the "update" action.',
        );
        $fields = [];
        preg_match_all(
            '@<input[^>]+type="hidden"[^>]+name="([^"]+)"[^>]+value="([^"]*)"@',
            $formMatch[2],
            $matches,
            PREG_SET_ORDER,
        );
        foreach ($matches as $match) {
            $fields[html_entity_decode($match[1])] = html_entity_decode($match[2]);
        }
        $this->assertNotEmpty($fields, 'The profile update form contains no hidden fields.');
        return [
            'action' => html_entity_decode($formMatch[1]),
            'fields' => $fields,
        ];
    }

    /**
     * @param array<string, string> $submittedProperties property name to value, the only
     *                                                   `profileFormData` keys that are posted
     */
    protected function submitProfileForm(string $formUrl, array $submittedProperties): ResponseInterface
    {
        $submitData = $this->renderEditFormAndExtractSubmitData($formUrl);
        $parsedBody = $this->pluginArgumentsOfFormAction($submitData['action']);
        foreach ($submitData['fields'] as $name => $value) {
            $this->addFormValue($parsedBody, $name, $value);
        }
        foreach ($submittedProperties as $propertyName => $value) {
            $this->addFormValue(
                $parsedBody,
                sprintf('tx_academicpersonsedit_profileediting[profileFormData][%s]', $propertyName),
                $value,
            );
        }
        $body = new Stream('php://temp', 'rw');
        $body->write(http_build_query($parsedBody));
        $body->rewind();
        $request = (new InternalRequest('https://www.acme.com/home'))
            ->withMethod('POST')
            ->withAddedHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody($body)
            ->withParsedBody($parsedBody);
        return $this->requestAsFrontendUser($request);
    }

    /**
     * Turns `a[b][c]` notation into the nested array the request expects.
     *
     * @param array<string, mixed> $target
     */
    protected function addFormValue(array &$target, string $name, string $value): void
    {
        $position = strpos($name, '[');
        if ($position === false) {
            $target[$name] = $value;
            return;
        }
        preg_match_all('@\[([^]]*)]@', $name, $matches);
        $keys = array_merge([substr($name, 0, $position)], $matches[1]);
        $current = &$target;
        foreach ($keys as $key) {
            if (!isset($current[$key]) || !is_array($current[$key])) {
                $current[$key] = [];
            }
            $current = &$current[$key];
        }
        $current = $value;
    }

    /**
     * @return array<string, mixed>
     */
    protected function pluginArgumentsOfFormAction(string $action): array
    {
        $query = parse_url($action, PHP_URL_QUERY);
        if (!is_string($query) || $query === '') {
            return [];
        }
        $parsed = [];
        parse_str($query, $parsed);
        $arguments = [];
        foreach ($parsed as $name => $value) {
            $arguments[(string)$name] = $value;
        }
        return $arguments;
    }

    /**
     * Returns the rendered profile detail page, which is the plugin view every image
     * action is reached from.
     */
    protected function getProfileShowPage(): string
    {
        $listPage = $this->getPageAsFrontendUser('https://www.acme.com/home');
        return $this->getPageAsFrontendUser($this->extractActionLink($listPage, 'show'));
    }
}
