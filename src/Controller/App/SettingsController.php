<?php
declare(strict_types=1);
namespace App\Controller\App;

use App\Controller\CoreAbstractController;
use App\Entity\Config;
use App\Enum\EventNameEnum;
use App\Service\ConfigService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Framework\Http\Objects\Request;
use Framework\Http\Objects\Response;
use Framework\Http\Validators\CSRFValidator;
use Framework\Http\ViewEngine\ViewEngineInterface;

class SettingsController extends CoreAbstractController
{
    protected Config $config;
    protected ConfigService $configService;
    protected CSRFValidator $CSRFValidator;

    public function __construct(ViewEngineInterface $viewEngine, EntityManager $entityManager)
    {
        $this->configService = new ConfigService($entityManager);
        $this->config = $this->configService->getConfig();
        $this->CSRFValidator = new CSRFValidator();
        parent::__construct($viewEngine, $entityManager);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $user = $request->getAttributes()['user'];
        $error = $this->getFlash($request, "settings.error");

        return $this->render('app/settings/index.twig', [
            'user' => $user,
            'config' => $this->config,
            'error' => $error
        ]);
    }

    /**
     * @param string $path
     * @param string $key
     * @param string $value
     * @return bool
     */
    protected function updateEnv(string $path, string $key, string $value): bool
    {
        if (!file_exists($path)) {
            return false;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        $updated = false;

        foreach ($lines as &$line) {
            if (trim($line) === '' || str_starts_with(trim($line), '#')) {
                continue;
            }

            if (preg_match('/^' . preg_quote($key, '/') . '=/i', $line)) {
                $line = $key . '=' . $value;
                $updated = true;
            }
        }

        if (!$updated) {
            $lines[] = $key . '="' . $value . '"';
        }

        return file_put_contents($path, implode(PHP_EOL, $lines)) !== false;
    }

    /**
     * @param Request $request
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function update(Request $request): Response
    {
        if(!$this->CSRFValidator->handle($request)) {
            $this->setFlash($request, "settings.error", "Invalid CSRF token.");
            return $this->redirect("/app/settings");
        }

        $user = $request->getAttributes()['user'];

        $smtpHost = $request->getBody("smtpHost");
        $smtpPort = $request->getBody("smtpPort");
        $smtpUser = $request->getBody("smtpUser");
        $smtpPass = $request->getBody("smtpPass");
        $smtpMail = $request->getBody("smtpMail");

        $updateEnv = [];

        if($smtpHost && $smtpHost !== $_ENV["MESSENGER_HOST"]) {
            $updateEnv["MESSENGER_HOST"] = $smtpHost;
        }

        if($smtpPort && $smtpPort !== $_ENV["MESSENGER_PORT"]) {
            $updateEnv["MESSENGER_PORT"] = $smtpPort;
        }

        if($smtpUser && $smtpUser !== $_ENV["MESSENGER_NAME"]) {
            $updateEnv["MESSENGER_NAME"] = $smtpUser;
        }

        if($smtpPass && $smtpPass !== $_ENV["MESSENGER_MAIL_PASSWORD"]) {
            $updateEnv["MESSENGER_MAIL_PASSWORD"] = $smtpPass;
        }

        if($smtpMail && $smtpMail !== $_ENV["MESSENGER_MAIL"]) {
            $updateEnv["MESSENGER_MAIL"] = $smtpMail;
        }

        $useRateLimiting = $request->getbody("useRateLimiting");
        $maxRequestsRateLimiting = $request->getBody("maxRequestsRateLimiting");
        $timeWindowRateLimiting = $request->getBody("timeWindowRateLimiting");

        if($useRateLimiting) {
            $updateEnv["RATE_LIMITER_ENABLED"] = "true";
        } else {
            $updateEnv["RATE_LIMITER_ENABLED"] = "false";
        }

        if($maxRequestsRateLimiting && $maxRequestsRateLimiting !== $_ENV["RATE_LIMITER_MAX_REQUESTS"]) {
            $updateEnv["RATE_LIMITER_MAX_REQUESTS"] = $maxRequestsRateLimiting;
        }

        if($timeWindowRateLimiting && $timeWindowRateLimiting !== $_ENV["RATE_LIMITER_TIME_WINDOW"]) {
            $updateEnv["RATE_LIMITER_TIME_WINDOW"] = $timeWindowRateLimiting;
        }

        $hashingIpAllowlist = $request->getBody("hashingIpAllowlist");
        if($hashingIpAllowlist) {
            $updateEnv["HASHING_IP_ALLOWLIST"] = preg_replace('/\s+/', '', $hashingIpAllowlist);
        }

        $envPath = __DIR__ ."/../../../.env";
        foreach($updateEnv as $name => $value) {
            $status = $this->updateEnv($envPath, $name, $value);
            if(!$status) {
                $this->setFlash($request, "settings.error", "Unable to update environment variable '$name'.");
                return $this->redirect("/app/settings");
            }
        }

        $instanceName = $request->getBody("instanceName");
        $baseUrl = $request->getBody("baseUrl");
        $keyPrefix = $request->getBody("keyPrefix");
        $keyPattern = $request->getBody("keyPattern");

        $minLength = $request->getBody("minLength");
        $passwordRequiresUppercase = $request->getBody("passwordRequiresUppercase");
        $passwordRequiresNumbers = $request->getBody("passwordRequiresNumbers");
        $passwordRequiresSpecial = $request->getBody("passwordRequiresSpecial");

        $config = $this->config;

        if($instanceName && $config->getInstanceName() !== $instanceName) {
            $config->setInstanceName($instanceName);
        }

        if($baseUrl && $config->getbaseUrl() !== $baseUrl) {
            $config->setbaseUrl($baseUrl);
        }

        if($keyPrefix && $config->getKeyPrefix() !== $keyPrefix) {
            $config->setKeyPrefix($keyPrefix);
        }

        if($keyPattern && $config->getKeyPattern() !== $keyPattern) {
            $config->setKeyPattern($keyPattern);
        }

        if($minLength && $config->getMinPasswordLength() !== $minLength) {
            $config->setMinPasswordLength((int) $minLength);
        }

        if($passwordRequiresUppercase && $config->isPasswordRequiresUppercase() !== $passwordRequiresUppercase) {
            $config->setPasswordRequiresUppercase((bool) $passwordRequiresUppercase);
        }

        if($passwordRequiresNumbers && $config->isPasswordRequiresNumbers() !== $passwordRequiresNumbers) {
            $config->setPasswordRequiresNumbers((bool) $passwordRequiresNumbers);
        }

        if($passwordRequiresSpecial && $config->isPasswordRequiresSpecial() !== $passwordRequiresSpecial) {
            $config->setPasswordRequiresSpecial((bool) $passwordRequiresSpecial);
        }

        try {
            $this->entityManager->persist($config);
            $this->entityManager->flush();

            $this->auditLogService->log("settings.update.success", [
                'user_agent' => $request->getHeader("User-Agent"),
                'ip' => $request->getUserIp()
            ], "Settings has been successfully updated.", user: $user);

            $this->eventBusService->triggerEvent(
                EventNameEnum::SETTINGS_UPDATE_SUCCESS,
                [
                    'user_agent' => $request->getHeader("User-Agent"),
                    'ip' => $request->getUserIp(),
                    "author" => $user->getEmail()
                ]
            );
        } catch (\Throwable $e) {
            $this->eventBusService->triggerEvent(
                EventNameEnum::SETTINGS_UPDATE_FAILURE,
                [
                    'user_agent' => $request->getHeader("User-Agent"),
                    'ip' => $request->getUserIp(),
                    "author" => $user->getEmail()
                ]
            );

            $this->auditLogService->log("settings.update.error", [
                'user_agent' => $request->getHeader("User-Agent"),
                'ip' => $request->getUserIp()
            ], "An error occurred while updating settings: " . $e->getMessage(), user: $user);

            $this->setflash($request, "settings.error", "Something went wrong: " . $e->getMessage());
        }

        return $this->redirect("/app/settings");
    }
}