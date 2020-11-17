<?php

namespace App\Controller;

use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/travis/deploy-numbernine", name="travis_deploy_numbernine", methods={"POST"})
 */
class TravisDeployNumberNineAction extends AbstractController
{
    public function __invoke(Request $request, KernelInterface $kernel): JsonResponse
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput(
            [
                'command' => 'app:numbernine:update',
                'docker-image' => $this->getParameter('travis_deploy_docker_image'),
                'destination-volume' => $this->getParameter('travis_deploy_destination_volume'),
            ]
        );
        $output = new BufferedOutput();
        $returnCode = $application->run($input, $output);

        if ($returnCode !== 0) {
            return $this->json(
                [
                    'message' => 'An error occured during deployment process',
                    'error' => $output->fetch(),
                    'errorCode' => $returnCode,
                ],
                500
            );
        }

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
