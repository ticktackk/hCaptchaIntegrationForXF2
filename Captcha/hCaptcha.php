<?php

namespace TickTackk\hCaptchaIntegration\Captcha;

use XF\App as BaseApp;
use XF\Captcha\AbstractCaptcha;
use XF\Template\Templater;
use GuzzleHttp\Exception\RequestException as GuzzleHttpRequestException;
use ArrayObject;
use XF\Http\Request as HttpRequest;
use XF\SubContainer\Http as HttpSubContainer;
use GuzzleHttp\Client as GuzzleHttpClient;

/**
 * Class hCaptcha
 *
 * @package TickTackk\hCaptchaIntegration\Captcha
 */
class hCaptcha extends AbstractCaptcha
{
    /**
     * hCAPTCHA site key
     *
     * @var null|string
     */
    protected $siteKey = null;

    /**
     * hCAPTCHA secret key
     *
     * @var null|string
     */
    protected $secretKey = null;

    /**
     * Enable hCAPTCHA invisible mode
     *
     * @var bool
     */
    protected $invisibleMode = false;

    /**
     * hCaptcha constructor.
     *
     * @param BaseApp $app
     */
    public function __construct(BaseApp $app)
    {
        parent::__construct($app);

        $extraKeys = $this->options()->extraCaptchaKeys;

        if (!empty($extraKeys['tckHCaptchaSiteKey']) && !empty($extraKeys['tckHCaptchaSecretKey']))
        {
            $this->siteKey = $extraKeys['tckHCaptchaSiteKey'];
            $this->secretKey = $extraKeys['tckHCaptchaSecretKey'];
        }

        if (!empty($extraKeys['tckHCaptchaInvisible']))
        {
            $this->invisibleMode = $extraKeys['tckHCaptchaInvisible'];
        }
    }

    /**
     * @return string|null
     */
    protected function getSiteKey() :? string
    {
        return $this->siteKey;
    }

    /**
     * @return string|null
     */
    protected function getSecretKey() :? string
    {
        return $this->secretKey;
    }

    /**
     * @return bool
     */
    protected function isInvisibleMode() : bool
    {
        return $this->invisibleMode;
    }

    /**
     * @return bool
     */
    protected function isForcedVisible() : bool
    {
        return $this->forceVisible;
    }

    /**
     * @param Templater $templater
     *
     * @return string
     */
    public function renderInternal(Templater $templater) : string
    {
        $siteKey = $this->getSiteKey();
        if (!$siteKey)
        {
            return '';
        }

        return $templater->renderTemplate('public:tckHCaptchaIntegration_captcha_hcaptcha', [
            'siteKey' => $siteKey,
            'invisible' => $this->isInvisibleMode() && !$this->isForcedVisible()
        ]);
    }

    public function isValid() : bool
    {
        $siteKey = $this->getSiteKey();
        $secretKey = $this->getSecretKey();

        if (!$siteKey || !$secretKey)
        {
            return true; // if not configured, always pass
        }

        $request = $this->request();
        $captchaResponse = $request->filter('h-captcha-response', 'str');
        if (!$captchaResponse)
        {
            return false;
        }

        try
        {
            $addOns = $this->app->container('addon.cache');
            $addOnVersion = $addOns['TickTackk\hCaptchaIntegration'] ?? 0 >= 1000011;

            $response = $this->httpClient()->post('https://hcaptcha.com/siteverify', [
                'form_params' => [
                    'sitekey' => $siteKey,
                    'secret' => $secretKey,
                    'response' => $captchaResponse,
                    'remoteip' => $request->getIp()
                ],
                'headers' => [
                    'XF-TCK-ADDON-VER' => $addOnVersion
                ]
            ])->getBody()->getContents();
            $response = \GuzzleHttp\json_decode($response, true);

            if (isset($response['success']) && isset($response['hostname']) && $response['hostname'] == $request->getHost())
            {
                return $response['success'];
            }

            return false;
        }
        catch(GuzzleHttpRequestException $e)
        {
            // this is an exception with the underlying request, so let it go through
            \XF::logException($e, false, 'hCAPTCHA connection error: ');

            return true;
        }
    }

    /**
     * @return BaseApp
     */
    protected function app() : BaseApp
    {
        return $this->app;
    }

    /**
     * @return ArrayObject
     */
    protected function options() : ArrayObject
    {
        return $this->app()->options();
    }

    /**
     * @return HttpRequest
     */
    protected function request() : HttpRequest
    {
        return $this->app()->request();
    }

    /**
     * @return HttpSubContainer
     */
    protected function http() : HttpSubContainer
    {
        return $this->app()->http();
    }

    /**
     * @return GuzzleHttpClient
     */
    protected function httpClient() : GuzzleHttpClient
    {
        return $this->http()->client();
    }
}