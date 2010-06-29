<?php

// Regex to filter out the client identifier
// (described in Section 2 of IETF draft)
// IETF draft does not prescribe a format for these, however
// I've arbitrarily chosen alphanumeric strings with hyphens and underscores, 3-12 characters long
// Feel free to change.
define("REGEX_CLIENT_ID", "/^[a-z0-9-_]{3,12}$/i");

// Used to define the name of the OAuth access token parameter (POST/GET/etc.)
// IETF Draft sections 5.2 and 5.3 specify that it should be called "oauth_token"
// but other implementations use things like "access_token"
// I won't be heartbroken if you change it, but it might be better to adhere to the spec
define("OAUTH_TOKEN_PARAM_NAME", "oauth_token");

// Client types (for client authorization)
define("WEB_SERVER_CLIENT_TYPE", "web_server");
define("USER_AGENT_CLIENT_TYPE", "user_agent");
define("REGEX_CLIENT_TYPE", "/^(web_server|user_agent)$/");

// Grant Types (for token obtaining)
define("AUTH_CODE_GRANT_TYPE", "authorization_code");
define("USER_CREDENTIALS_GRANT_TYPE", "user_basic_credentials");
define("ASSERTION_GRANT_TYPE", "assertion");
define("REFRESH_TOKEN_GRANT_TYPE", "refresh_token");
define("NONE_GRANT_TYPE", "none");
define("REGEX_TOKEN_GRANT_TYPE", "/^(authorization_code|user_basic_credentials|assertion|refresh_token|none)$/");

/* Error handling constants */

// HTTP status codes
define("ERROR_NOT_FOUND", "404 Not Found");
define("ERROR_BAD_REQUEST", "400 Bad Request");

// "Official" OAuth 2.0 errors
define("ERROR_REDIRECT_URI_MISMATCH", "redirect_uri_mismatch");
define("ERROR_BAD_AUTHORIZATION_CODE", "bad_authorization_code");
define("ERROR_INVALID_CLIENT_CREDENTIALS", "invalid_client_credentials");
define("ERROR_UNAUTHORIZED_CLIENT", "unauthorized_client");
define("ERROR_INVALID_ASSERTION", "invalid_assertion");
define("ERROR_UNKNOWN_FORMAT", "unknown_format");
define("ERROR_AUTHORIZATION_EXPIRED", "authorization_expired");
define("ERROR_MULTIPLE_CREDENTIALS", "multiple_credentials");
define("ERROR_INVALID_USER_CREDENTIALS", "invalid_user_credentials");
define("ERROR_USER_DENIED", "user_denied");

// Errors that we made up

// http://tools.ietf.org/html/draft-ietf-oauth-v2-08#section-5
// says that only one auth method should be used
// but it doesn't define an error to return
define("ERROR_MULTIPLE_AUTHORIZATION_METHODS", "multiple_authorization_methods");

// Error for trying to use a grant type that we haven't implemented
define("ERROR_UNSUPPORTED_GRANT_TYPE", "unsupported_grant_type");

// When accessing a protected resource, an access token must only be supplied
// via one method -- if more than one method is used, throw an error
define("ERROR_MULTIPLE_ACCESS_TOKENS", "multiple_access_tokens");

// When accessing a protected resource,
// we need an errors for an expired access code,
// an out-of-scope resource, and an invalid access code
define("ERROR_ACCESS_TOKEN_EXPIRED", "token_expired");
define("ERROR_ACCESS_OUT_OF_SCOPE", "access_out_of_scope");
define("ERROR_INVALID_ACCESS_TOKEN", "invalid_token");

abstract class OAuth2 {

    /* Subclasses must implement the following functions */

    // Make sure that the client id is valid
    // If a secret is required, check that they've given the right one
    // Must return false if the client credentials are invalid
    abstract protected function auth_client_credentials($client_id, $client_secret = null);

    // OAuth says we should store request URIs for each registered client
    // Implement this function to grab the stored URI for a given client id
    abstract protected function get_redirect_uri($client_id);

    // We need to store and retrieve access token data as we create and verify tokens
    // Implement these functions to do just that
    
    // Look up the supplied token id from storage, and return an array like:
    //
    //  array(
    //      "client_id" => <stored client id>,
    //      "expires" => <stored expiration timestamp>,
    //      "scope" => <stored scope (may be null)
    //  )
    //  
    //  Return null if the supplied token is invalid
    //
    abstract protected function get_access_token($token_id);

    // Store the supplied values
    abstract protected function store_access_token($token_id, $client_id, $expires, $scope = null);

    /*
     * 
     * Stuff that should get overridden by subclasses
     * 
     * I don't want to make these abstract, because then subclasses would have
     * to implement all of them, which is too much work.
     * 
     * So they're just stubs.  Override the ones you need.
     *
     */

    // You should override this function with something,
    // or else your OAuth provider won't support any grant types!
    protected function get_supported_grant_types() {
        // If you support all grant types, then you'd do:
        // return array(
        //             AUTH_CODE_GRANT_TYPE, 
        //             USER_CREDENTIALS_GRANT_TYPE, 
        //             ASSERTION_GRANT_TYPE, 
        //             REFRESH_TOKEN_GRANT_TYPE, 
        //             NONE_GRANT_TYPE
        //     );
        
        return array();
    }

    // If you want to restrict clients to certain grant types, override this function
    // Given a client identifier and grant type, return true or false
    protected function authorize_client($client_id, $grant_type) {
        return true;
    }

    /* Functions that help grant access tokens for various grant types */

    // Fetch authorization code data (probably the most common grant type)
    // IETF Draft 4.1.1: http://tools.ietf.org/html/draft-ietf-oauth-v2-08#section-4.1.1
    // Required for AUTH_CODE_GRANT_TYPE
    protected function get_stored_auth_code($code) {
        // Retrieve the stored data for the given authorization code
        // Should return:
        //
        // array (
        //  "client_id" => <stored client id>,
        //  "redirect_uri" => <stored redirect URI>,
        //  "expires" => <stored code expiration time>,
        //  "scope" => <stored scope values (space-separated string), or can be omitted if scope is unused>
        // )
        //
        // Return null if the code is invalid.

        return null;
    }

    // Take the provided authorization code values and store them somewhere (db, etc.)
    // Required for AUTH_CODE_GRANT_TYPE
    protected function store_auth_code($code, $client_id, $redirect_uri, $expires, $scope) {
        // This function should be the storage counterpart to get_stored_auth_code

        // If storage fails for some reason, we're not currently checking
        // for any sort of success/failure, so you should bail out of the
        // script and provide a descriptive fail message
    }

    // Grant access tokens for basic user credentials
    // IETF Draft 4.1.2: http://tools.ietf.org/html/draft-ietf-oauth-v2-08#section-4.1.2
    // Required for USER_CREDENTIALS_GRANT_TYPE
    protected function check_user_credentials($client_id, $username, $password) {
        // Check the supplied username and password for validity
        // You can also use the $client_id param to do any checks required
        // based on a client, if you need that
        // If the username and password are invalid, return false

        // If the username and password are valid, and you want to verify the scope of
        // a user's access, return an array with the scope values, like so:
        //
        // array (
        //  "scope" => <stored scope values (space-separated string)>
        // )
        //
        // We'll check the scope you provide against the requested scope before
        // providing an access token.
        //
        // Otherwise, just return true.

        return false;
    }

    // Grant access tokens for assertions
    // IETF Draft 4.1.3: http://tools.ietf.org/html/draft-ietf-oauth-v2-08#section-4.1.3
    // Required for ASSERTION_GRANT_TYPE
    protected function check_assertion($client_id, $assertion_type, $assertion) {
        // Check the supplied assertion for validity
        // You can also use the $client_id param to do any checks required
        // based on a client, if you need that
        // If the assertion is invalid, return false

        // If the assertion is valid, and you want to verify the scope of
        // an access request, return an array with the scope values, like so:
        //
        // array (
        //  "scope" => <stored scope values (space-separated string)>
        // )
        //
        // We'll check the scope you provide against the requested scope before
        // providing an access token.
        //
        // Otherwise, just return true.

        return false;
    }
    
    // Grant refresh access tokens
    // IETF Draft 4.1.4: http://tools.ietf.org/html/draft-ietf-oauth-v2-08#section-4.1.4
    // Required for REFRESH_TOKEN_GRANT_TYPE
    protected function get_refresh_token($refresh_token) {
        // Retrieve the stored data for the given refresh token
        // Should return:
        //
        // array (
        //  "client_id" => <stored client id>,
        //  "expires" => <refresh token expiration time>,
        //  "scope" => <stored scope values (space-separated string), or can be omitted if scope is unused>
        // )
        //
        // Return null if the token id is invalid.

        return null;
    }

    // Store refresh access tokens
    // Required for REFRESH_TOKEN_GRANT_TYPE
    protected function store_refresh_token($token, $client_id, $expires, $scope = null) {
        // If storage fails for some reason, we're not currently checking
        // for any sort of success/failure, so you should bail out of the
        // script and provide a descriptive fail message

        return;
    }

    // Grant access tokens for the "none" grant type
    // Not really described in the IETF Draft, so I just left a method stub...do whatever you want!
    // Required for NONE_GRANT_TYPE
    protected function check_none_access($client_id) {
        return false;
    }

    protected function get_default_authentication_realm() {
        // Change this to whatever authentication realm you want to send in a WWW-Authenticate header
        return "Service";
    }

    /* End stuff that should get overridden */

    private $access_token_lifetime = 3600;
    private $auth_code_lifetime = 30;
    private $refresh_token_lifetime = 1209600; // Two weeks

    public function __construct($access_token_lifetime = 3600, $auth_code_lifetime = 30, $refresh_token_lifetime = 1209600) {
        $this->access_token_lifetime = $access_token_lifetime;
        $this->auth_code_lifetime = $auth_code_lifetime;
        $this->refresh_token_lifetime = $refresh_token_lifetime;
    }

    /* Resource protecting (Section 5) */

    // Check that a valid access token has been provided
    //
    // The scope parameter defines any required scope that the token must have
    // If a scope param is provided and the token does not have the required scope,
    // we bounce the request
    //
    // Some implementations may choose to return a subset of the protected resource
    // (i.e. "public" data) if the user has not provided an access token
    // or if the access token is invalid or expired
    //
    // The IETF spec says that we should send a 401 Unauthorized header and bail immediately
    // so that's what the defaults are set to
    //
    // Here's what each parameter does:
    // $scope = A space-separated string of required scope(s), if you want to check for scope
    // $exit_not_present = If true and no access token is provided, send a 401 header and exit, otherwise return false
    // $exit_invalid = If true and the implementation of get_access_token returns null, exit, otherwise return false
    // $exit_expired = If true and the access token has expired, exit, otherwise return false
    // $exit_scope = If true the access token does not have the required scope(s), exit, otherwise return false
    // $realm = If you want to specify a particular realm for the WWW-Authenticate header, supply it here
    public function verify_access_token($scope = null, $exit_not_present = true, $exit_invalid = true, $exit_expired = true, $exit_scope = true, $realm = null) {
        $token_param = $this->get_access_token_param();
        if ($token_param === false) { // Access token was not provided
            return $exit_not_present ? $this->send_401_unauthorized($realm) : false;
        }

        // Get the stored token data (from the implementing subclass)
        $token = $this->get_access_token($token_param);
        if ($token === null)
            return $exit_invalid ? $this->send_401_unauthorized($realm, ERROR_INVALID_ACCESS_TOKEN) : false;

        // Check token expiration
        if (isset($token["expires"]) && time() > $token["expires"])
            return $exit_expired ? $this->send_401_unauthorized($realm, ERROR_ACCESS_TOKEN_EXPIRED) : false;

        // Check scope, if provided
        // If token doesn't have a scope, it's null/empty, or it's insufficient, then throw an error
        if ($scope &&
                (!isset($token["scope"]) || !$token["scope"] || !$this->check_scope($scope, $token["scope"])))
            return $exit_scope ? $this->send_401_unauthorized($realm, ERROR_ACCESS_OUT_OF_SCOPE) : false;

        return true;
    }


    // Returns true if everything in required scope is contained in available scope
    // False if something in required scope is not in available scope
    private function check_scope($required_scope, $available_scope) {
        // The required scope ($scope) should match or be a subset of the token's scope
        $required_scope = explode(" ", $scope);
        $token_scope = explode(" ", $token["scope"]);

        return (count(array_diff($required_scope, $token_scope)) == 0);
    }

    // Send a 401 unauthorized header with the given realm
    // and an error, if provided
    private function send_401_unauthorized($realm, $error = null) {
        $realm = $realm === null ? $this->get_default_authentication_realm() : $realm;

        $auth_header = "WWW-Authenticate: Token realm='".$realm."'";

        if ($error !== null)
            $auth_header .= ", error='".$error."'";

        header("HTTP/1.1 401 Unauthorized");
        header($auth_header);

        exit;
    }

    // Pulls the access token out of the HTTP request
    // Either from the Authorization header or GET/POST/etc.
    // Returns false if no token is present
    // TODO: Support POST or DELETE
    private function get_access_token_param() {
        $auth_header = $this->get_authorization_header();

        if ($auth_header !== false) {
            // Make sure only the auth header is set
            if (isset($_GET[OAUTH_TOKEN_PARAM_NAME]) || isset($_POST[OAUTH_TOKEN_PARAM_NAME]))
                $this->error(ERROR_BAD_REQUEST, ERROR_MULTIPLE_ACCESS_TOKENS);

            $auth_header = trim($auth_header);

            // Make sure it's Token authorization
            if (strcmp(substr($auth_header, 0, 6),"Token ") !== 0)
                $this->error(ERROR_BAD_REQUEST, ERROR_UNKNOWN_FORMAT);

            // Parse the rest of the header
            if (preg_match('/\s*token\s*="(.+)"/', substr($auth_header, 6), $matches) == 0 || count($matches) < 2)
                $this->error(ERROR_BAD_REQUEST, ERROR_UNKNOWN_FORMAT);

            return $matches[1];
        }

        if (isset($_GET[OAUTH_TOKEN_PARAM_NAME]))  {
            if (isset($_POST[OAUTH_TOKEN_PARAM_NAME])) // Both GET and POST are not allowed
                $this->error(ERROR_BAD_REQUEST, ERROR_MULTIPLE_ACCESS_TOKENS);

            return $_GET[OAUTH_TOKEN_PARAM_NAME];
        }

        if (isset($_POST[OAUTH_TOKEN_PARAM_NAME]))
            return $_POST[OAUTH_TOKEN_PARAM_NAME];

        return false;
    }

    /* Access token granting (Section 4) */

    // Grant or deny a requested access token
    // This would be called from the "/token" endpoint as defined in the spec
    // Obviously, you can call your endpoint whatever you want
    public function grant_access_token() {
        $filters = array(
            "grant_type" => array("filter" => FILTER_VALIDATE_REGEXP, "options" => array("regexp" => REGEX_TOKEN_GRANT_TYPE), "flags" => FILTER_REQUIRE_SCALAR),
            "scope" => array("flags" => FILTER_REQUIRE_SCALAR),
            "code" => array("flags" => FILTER_REQUIRE_SCALAR),
            "redirect_uri" => array("filter" => FILTER_VALIDATE_URL, "flags" => array(FILTER_FLAG_SCHEME_REQUIRED, FILTER_REQUIRE_SCALAR)),
            "username" => array("flags" => FILTER_REQUIRE_SCALAR),
            "password" => array("flags" => FILTER_REQUIRE_SCALAR),
            "assertion_type" => array("flags" => FILTER_REQUIRE_SCALAR),
            "assertion" => array("flags" => FILTER_REQUIRE_SCALAR),
            "refresh_token" => array("flags" => FILTER_REQUIRE_SCALAR),
        );

        $input = filter_input_array(INPUT_POST, $filters);

        // Grant Type must be specified.  Draft doesn't specify a specific error
        if (!$input["grant_type"])
            $this->error(ERROR_BAD_REQUEST, ERROR_UNKNOWN_FORMAT);

        // Make sure we've implemented the requested grant type
        if (!in_array($input["grant_type"], $this->get_supported_grant_types()))
            $this->error(ERROR_BAD_REQUEST, ERROR_UNSUPPORTED_GRANT_TYPE);

        // Authorize the client
        $client = $this->get_client_credentials();

        if ($this->auth_client_credentials($client[0], $client[1]) === false)
            $this->error(ERROR_BAD_REQUEST, ERROR_INVALID_CLIENT_CREDENTIALS);

        if (!$this->authorize_client($client[0], $input["grant_type"]))
            $this->error(ERROR_BAD_REQUEST, ERROR_UNAUTHORIZED_CLIENT);

        // Do the granting
        switch ($input["grant_type"]) {
            case AUTH_CODE_GRANT_TYPE:
                if (!$input["code"] || !$input["redirect_uri"])
                    $this->error(ERROR_BAD_REQUEST, ERROR_UNKNOWN_FORMAT);

                $stored = $this->get_stored_auth_code($input["code"]);

                if ($stored === false || $input["redirect_uri"] != $stored["redirect_uri"] || $client[0] != $stored["client_id"])
                    $this->error(ERROR_BAD_REQUEST, ERROR_BAD_AUTHORIZATION_CODE);

                if ($stored["expires"] > time())
                    $this->error(ERROR_BAD_REQUEST, ERROR_AUTHORIZATION_EXPIRED);

                break;
            case USER_CREDENTIALS_GRANT_TYPE:
                if (!$input["username"] || !$input["password"])
                    $this->error(ERROR_BAD_REQUEST, ERROR_UNKNOWN_FORMAT);

                $stored = $this->check_user_credentials($client[0], $input["username"], $input["password"]);

                if ($stored === false)
                    $this->error(ERROR_BAD_REQUEST, ERROR_INVALID_USER_CREDENTIALS);

                break;
            case ASSERTION_GRANT_TYPE:
                if (!$input["assertion_type"] || $input["assertion"])
                    $this->error(ERROR_BAD_REQUEST, ERROR_UNKNOWN_FORMAT);

                $stored = $this->check_assertion($client[0], $input["assertion_type"], $input["assertion"]);

                if ($stored === false)
                    $this->error(ERROR_BAD_REQUEST, ERROR_INVALID_ASSERTION);

                break;
            case REFRESH_TOKEN_GRANT_TYPE:
                if (!$input["refresh_token"])
                    $this->error(ERROR_BAD_REQUEST, ERROR_UNKNOWN_FORMAT);

                $stored = $this->get_refresh_token($input["refresh_token"]);

                if ($stored === null || $client[0] != $stored["client_id"])
                    $this->error(ERROR_BAD_REQUEST, ERROR_BAD_AUTHORIZATION_CODE);

                if ($stored["expires"] > time())
                    $this->error(ERROR_BAD_REQUEST, ERROR_AUTHORIZATION_EXPIRED);
                
                break;
            case NONE_GRANT_TYPE:
                $stored = $this->check_none_access($client[0]);

                if ($stored === false)
                    $this->error(ERROR_BAD_REQUEST, ERROR_UNKNOWN_FORMAT);
        }

        // Check scope, if provided
        if ($input["scope"] && (!is_array($stored) || !isset($stored["scope"]) || !$this->check_scope($input["scope"], $stored["scope"])))
            $this->error(ERROR_BAD_REQUEST, ERROR_ACCESS_OUT_OF_SCOPE);

        if (!$input["scope"])
            $input["scope"] = null;

        $token = $this->create_access_token($client[0], $input["scope"]);

        $this->send_json_headers();
        echo json_encode($token);
    }

    // Internal function used to get the client credentials from HTTP basic auth or POST data
    // See http://tools.ietf.org/html/draft-ietf-oauth-v2-08#section-2
    private function get_client_credentials() {
        if (isset($_SERVER["PHP_AUTH_USER"]) && $_POST && isset($_POST["client_id"]))
            $this->error(ERROR_BAD_REQUEST, ERROR_MULTIPLE_CREDENTIALS);

        // Try basic auth
        if (isset($_SERVER["PHP_AUTH_USER"]))
            return array($_SERVER["PHP_AUTH_USER"], $_SERVER["PHP_AUTH_PW"]);

        // Try POST
        if ($_POST && isset($_POST["client_id"])) {
            if (isset($_POST["client_secret"]))
                return array($_POST["client_id"], $_POST["client_secret"]);

            return array($_POST["client_id"], NULL);
        }

        // No credentials were specified
        $this->error(ERROR_BAD_REQUEST, ERROR_INVALID_CLIENT_CREDENTIALS);
    }

    /* End-user/client Authorization (Section 3 of IETF Draft) */

    // Pull the authorization request data out of the HTTP request
    // and return it so the authorization server can prompt the user
    // for approval
    public function get_authorize_params() {
        $filters = array(
            "client_id" => array("filter" => FILTER_VALIDATE_REGEXP, "options" => array("regexp" => REGEX_CLIENT_ID), "flags" => FILTER_REQUIRE_SCALAR),
            "type" => array("filter" => FILTER_VALIDATE_REGEXP, "options" => array("regexp" => REGEX_CLIENT_TYPE), "flags" => FILTER_REQUIRE_SCALAR),
            "redirect_uri" => array("filter" => FILTER_VALIDATE_URL, "flags" => array(FILTER_FLAG_SCHEME_REQUIRED, FILTER_REQUIRE_SCALAR)),
            "state" => array("flags" => FILTER_REQUIRE_SCALAR),
            "scope" => array("flags" => FILTER_REQUIRE_SCALAR),
        );

        $input = filter_input_array(INPUT_GET, $filters);

        // type and client_id are required
        // IETF draft does not specify an error to use, so I've chosen "unknown_format"
        // TODO: Perhaps we should define more specific errors?
        if (!$input["type"] || !$input["client_id"])
            $this->error(ERROR_BAD_REQUEST, ERROR_UNKNOWN_FORMAT);

        // redirect_uri is technically not required if already established via other channels
        // check an existing redirect URI against the one supplied
        $redirect_uri = $this->get_redirect_uri($input["client_id"]);

        // At least one of: existing redirect URI or input redirect URI must be specified
        // draft doesn't define an error for this either
        if (!$redirect_uri && !$input["redirect_uri"])
            $this->error(ERROR_BAD_REQUEST, ERROR_UNKNOWN_FORMAT);

        // If there's an existing uri and one from input, verify that they match
        if ($redirect_uri && $input["redirect_uri"]) {
            // Ensure that the input uri starts with the stored uri
            if (strcasecmp(substr($input["redirect_uri"], 0, strlen($redirect_uri)),$redirect_uri) !== 0)
                $this->error(ERROR_BAD_REQUEST, ERROR_REDIRECT_URI_MISMATCH);
        } elseif ($redirect_uri) { // They did not provide a uri from input, so use the stored one
            $input["redirect_uri"] = $redirect_uri;
        }

        return $input;
    }

    // After the user has approved or denied the access request
    // the authorization server should call this function to redirect
    // the user appropriately

    // The params all come from the results of get_authorize_params
    // except for $is_authorized -- this is true or false depending on whether
    // the user authorized the access
    public function finish_client_authorization($is_authorized, $client_type, $client_id, $redirect_uri, $state, $scope = null) {
        if ($state !== null)
            $return["state"] = $state;

        if ($is_authorized === false) {
            $return["error"] = ERROR_USER_DENIED;
        } elseif ($client_type == USER_AGENT_CLIENT_TYPE){
            $uri_char = "#";
            //  Generate a full access token for user_agent
            $token = $this->create_access_token($client_id, $scope);
            $return = array_merge($return, $token); // Put the token values in the return array
        } else { // Generate an auth code for anything else
            $uri_char = "?";
            $return["code"] = $this->gen_auth_code();
            $this->store_auth_code($return["code"], $client_id, $redirect_uri, time() + $this->auth_code_lifetime, $scope);
        }

        // Make sure that the uri contains our special character (either ? or #)
        // If our char isn't present, we append it
        // If the uri doesn't end in the char, we'll append & as well to avoid
        // existing params the client may have supplied
        $uri_char_pos = strpos($redirect_uri, $uri_char);
        if ($uri_char_pos === false) // The URI does not contain our character; append it
            $redirect_uri .= $uri_char;
        elseif ($uri_char_pos != (strlen($redirect_uri) - 1)) // the URI contains but does not end with the special char
            $redirect_uri .= "&";

        header("HTTP/1.1 302 Found");
        header("Location: " . $redirect_uri . http_build_query($return));

        exit;
    }

    /* Other/utility functions */

    // This belongs in a separate factory, but to keep it simple, I'm just keeping it here.
    private function create_access_token($client_id, $scope) {
        $token = array(
            "access_token" => $this->gen_access_token(),
            "expires_in" => $this->access_token_lifetime,
            "scope" => $scope
        );

        $this->store_access_token($token["access_token"], $client_id, time() + $this->access_token_lifetime, $scope);

        // Issue a refresh token also, if we support them
        if (in_array(REFRESH_TOKEN_GRANT_TYPE, $this->get_supported_grant_types())) {
            $token["refresh_token"] = $this->gen_access_token();
            $this->store_refresh_token($token["refresh_token"], $client_id, time() + $this->refresh_token_lifetime, $scope);
        }

        return $token;
    }

    // Implementing classes may want to override these two functions
    // to implement other access token or auth code generation schemes
    private function gen_access_token() {
        return base64_encode(pack('N6', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand()));
    }

    private function gen_auth_code() {
        return base64_encode(pack('N6', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand()));
    }

    // Implementing classes may need to override this function for use on non-Apache web servers
    // Just pull out the Authorization HTTP header and return it
    // Return false if the Authorization header does not exist
    private function get_authorization_header() {
        if (array_key_exists("HTTP_AUTHORIZATION", $_SERVER))
            return $_SERVER["HTTP_AUTHORIZATION"];

        if (function_exists("apache_request_headers")) {
            $headers = apache_request_headers();

            if (array_key_exists("Authorization", $headers))
                return $headers["Authorization"];
        }

        return false;
    }

    private function send_json_headers() {
        header("Content-Type: application/json");
        header("Cache-Control: no-store");
    }

    public function error($code, $message = null) {
        header("HTTP/1.1 " . $code);

        if ($message) {
            $this->send_json_headers();
            echo json_encode(array("error" => $message));
        }

        exit;
    }
}