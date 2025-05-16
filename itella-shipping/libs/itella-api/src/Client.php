<?php
namespace Mijora\Itella;

use Mijora\Itella\ItellaException;

use Pakettikauppa\Client as _Client;

class Client
{
    private $urlBase;
    private $urlAuth;
    private $user;
    private $pass;
    private $isTest = false;

    /** @var \Pakettikauppa\Client */
    private $_client;

    public function __construct($user, $pass, $isTest = false)
    {
        $this->user = $user;
        $this->pass = $pass;
        $this->isTest = $isTest;

        $this->urlBase = 'https://nextshipping.posti.fi';
        $this->urlAuth = 'https://oauth2.posti.com';
        if ($this->isTest) {
            $this->urlBase = 'https://tst.ecomplugin.postinext.fi';
            $this->urlAuth = 'https://oauth2.barium.posti.com';
        }

        $this->_client = new _Client(
            array(
                'pakettikauppa_config' => array(
                    'api_key' => $this->user,
                    'secret' => $this->pass,
                    'base_uri' => $this->urlBase,
                    'use_posti_auth' => true,
                    'posti_auth_url' => $this->urlAuth,
                ),
            ),
            'pakettikauppa_config'
        );
    }

    public function getAccessToken()
    {
        $token = $this->_client->getToken();

        if (!isset($token->access_token)) {
            $error = [];
            if (isset($token->status)) {
                $error[] = 'Status: ' . $token->status;
            }

            if (isset($token->error)) {
                $error[] = 'Error: ' . $token->error;
            }

            if (isset($token->message)) {
                $error[] = 'Message: ' . $token->message;
            }

            throw new ItellaException(implode("\n ", $error));
        }

        $this->_client->setAccessToken($token->access_token);
        return $token->access_token;
    }

    public function getAuthenticatedClient()
    {
        $this->getAccessToken();
        return $this->_client;
    }
}
