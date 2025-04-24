<?php
use \Firebase\JWT\JWT;

namespace EqualizeDigital\AccessibilityChecker\Tokens;

class JWT {
    private $secretKey;

    public function __construct( $secretKey ) {
        $this->secretKey = $secretKey;
    }

    public function generateToken( $payload ) {
        $issuedAt = time();
        $expirationTime = $issuedAt + 3600;  // jwt valid for 1 hour from the issued time
        $payload['iat'] = $issuedAt;
        $payload['exp'] = $expirationTime;

        return JWT::encode( $payload, $this->secretKey );
    }
}
