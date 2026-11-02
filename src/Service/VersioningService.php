<?php

    namespace App\Service;

    use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
    use Symfony\Component\HttpFoundation\RequestStack;

    class VersioningService
    {
        private string $defaultVersion;

        public function __construct
        (
            private readonly RequestStack $requestStack,
            private readonly ParameterBagInterface $parameterBag
        )
        {
            $this->defaultVersion = $this->parameterBag->get('default_api_version');
        }


        public function getVersion(): string
        {
            $version = $this->defaultVersion;

            $request = $this->requestStack->getCurrentRequest();
            $accept = $request->headers->get('Accept');

            // Récupérer le numéro de version dans la chaine de caractère de Accept
            $header = explode(';', $accept);

            // Parcourir toutes les entêtes pour trouver la version
            foreach ($header as $value) {
                if (str_contains($value, 'version')) {
                    $version = explode('=', $value);
                    $version = $version[1];
                    break;
                }
            }

            return $version;
        }
    }
