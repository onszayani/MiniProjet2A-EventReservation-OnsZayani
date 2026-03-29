<?php
require __DIR__.'/vendor/autoload.php';
use Symfony\Component\Dotenv\Dotenv;
(new Dotenv())->bootEnv(__DIR__.'/.env');
$kernel = new \App\Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();
$userRepo = $container->get(\Doctrine\ORM\EntityManagerInterface::class)->getRepository(\App\Entity\User::class);
$user = $userRepo->findOneBy(['email' => 'test@test.com']);
if (!$user) {
    $user = (new \App\Entity\User())->setEmail('test@test.com')->setDisplayName('Test');
}

$svc = $container->get(\App\Service\PasskeyAuthService::class);
try {
    $res = $svc->getRegistrationOptions($user);
    var_dump($res);
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
