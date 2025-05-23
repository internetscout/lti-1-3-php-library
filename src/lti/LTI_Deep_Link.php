<?php
namespace IMSGlobal\LTI;

use \Firebase\JWT\JWT;

class LTI_Deep_Link {

    private LTI_Registration $registration;
    private string $deployment_id;
    /** @var array<mixed> $deep_link_settings */
    private array $deep_link_settings;

    /**
     * @param array<mixed> $deep_link_settings
     */
    public function __construct(
        LTI_Registration $registration,
        string $deployment_id,
        array $deep_link_settings
    ) {
        $this->registration = $registration;
        $this->deployment_id = $deployment_id;
        $this->deep_link_settings = $deep_link_settings;
    }

    /**
     * @param array<LTI_Deep_Link_Resource> $resources
     */
    public function get_response_jwt(array $resources, ?string $kid = null): string {
        $message_jwt = [
            "iss" => $this->registration->get_client_id(),
            "aud" => $this->registration->get_issuer(),
            "exp" => time() + 600,
            "iat" => time(),
            "nonce" => 'nonce' . hash('sha256', random_bytes(64)),
            "https://purl.imsglobal.org/spec/lti/claim/deployment_id" => $this->deployment_id,
            "https://purl.imsglobal.org/spec/lti/claim/message_type" => "LtiDeepLinkingResponse",
            "https://purl.imsglobal.org/spec/lti/claim/version" => "1.3.0",
            "https://purl.imsglobal.org/spec/lti-dl/claim/content_items" => array_map(function($resource) { return $resource->to_array(); }, $resources),
            "https://purl.imsglobal.org/spec/lti-dl/claim/data" => $this->deep_link_settings['data'],
        ];

        return JWT::encode(
            $message_jwt,
            $this->registration->get_tool_private_key(),
            'RS256',
            is_null($kid) ?  $this->registration->get_kid() : $kid
        );
    }

    /**
     * @param array<LTI_Deep_Link_Resource> $resources
     */
    public function output_response_form(array $resources, ?string $kid = null): void {
        $jwt = $this->get_response_jwt($resources, $kid);
        ?>
        <form id="auto_submit" action="<?= $this->deep_link_settings['deep_link_return_url']; ?>" method="POST">
            <input type="hidden" name="JWT" value="<?= $jwt ?>" />
            <input type="submit" name="Go" />
        </form>
        <script>
            document.getElementById('auto_submit').submit();
        </script>
        <?php
    }
}
