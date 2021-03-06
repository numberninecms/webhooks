<?php

/*
 * This file is part of the NumberNine package.
 *
 * (c) William Arin <williamarin.dev@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/travis/{deployType<deploy|staging>}", name="travis_deploy", methods={"POST"})
 */
class TravisDeployAction extends AbstractController
{
    public function __invoke(Request $request, KernelInterface $kernel, string $deployType): JsonResponse
    {
        $this->validateRequest($request);

        chdir($this->getParameter("${deployType}_app_path"));
        exec('make deploy > /dev/null 2>&1 &');

        return $this->json(['message' => 'Deployment successful']);
    }

    private function validateRequest(Request $request): void
    {
        if ($request->headers->get('Content-Type') !== 'application/x-www-form-urlencoded') {
            throw new RuntimeException('Invalid content-type.', 415);
        }

        $payload = $request->request->get('payload');
        $travisApiConfig = json_decode((string)file_get_contents('https://api.travis-ci.com/config'));
        $publicKey = $travisApiConfig->config->notifications->webhook->public_key;
        $signature = base64_decode((string)$request->headers->get('Signature'));

        if (openssl_verify($payload, $signature, $publicKey) !== 1) {
            throw new RuntimeException('Access denied.', 401);
        }

        $data = json_decode($payload, false, 512, JSON_THROW_ON_ERROR);

        if ($data->result !== 0) {
            throw new RuntimeException('Build has failed, cancelling.', 500);
        }
    }
}
