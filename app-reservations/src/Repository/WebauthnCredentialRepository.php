<?php

namespace App\Repository;

use App\Entity\WebauthnCredential;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WebauthnCredential>
 */
class WebauthnCredentialRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WebauthnCredential::class);
    }

    //    /**
    //     * @return WebauthnCredential[] Returns an array of WebauthnCredential objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('w')
    //            ->andWhere('w.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('w.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?WebauthnCredential
    //    {
    //        return $this->createQueryBuilder('w')
    //            ->andWhere('w.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function saveCredential(
        User $user, PublicKeyCredentialSource $source, string $name='Ma passkey'
    ): WebauthnCredential {
        $c = new WebauthnCredential($user, $name);
        $c->setCredentialSource($source);
        $this->getEntityManager()->persist($c);
        $this->getEntityManager()->flush();
        return $c;
    }

    public function findByCredentialId(string $credentialId): ?WebauthnCredential
    {
        foreach ($this->findAll() as $cred) {
            if ($cred->getCredentialSource()->getPublicKeyCredentialId() === $credentialId)
                return $cred;
        }
        return null;
    }

}
