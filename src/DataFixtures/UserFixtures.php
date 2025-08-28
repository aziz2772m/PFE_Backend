<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixtures extends Fixture
{
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();

        // Create an admin user
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setNom($faker->lastName);
        $admin->setPrenom($faker->firstName);
        $admin->setSexe($faker->randomElement(['M', 'F']));
        $admin->setDateNaissance($faker->date('Y-m-d'));
        $admin->setRoles(['ROLE_ADMIN']); // Important: manually encode to string
        $admin->setPassword($this->passwordEncoder->encodePassword($admin, 'admin123'));
        $manager->persist($admin);

        // Create 5 regular users
        for ($i = 0; $i < 5; $i++) {
            $user = new User();
            $user->setEmail($faker->unique()->safeEmail);
            $user->setNom($faker->lastName);
            $user->setPrenom($faker->firstName);
            $user->setSexe($faker->randomElement(['M', 'F']));
            $user->setDateNaissance($faker->date('Y-m-d'));
            $user->setRoles(['ROLE_CHERCHEUR']);
            $user->setPassword($this->passwordEncoder->encodePassword($user, 'userpass'));
            $manager->persist($user);
        }
        for ($i = 0; $i < 2; $i++) {
            $user = new User();
            $user->setEmail($faker->unique()->safeEmail);
            $user->setNom($faker->lastName);
            $user->setPrenom($faker->firstName);
            $user->setSexe($faker->randomElement(['M', 'F']));
            $user->setDateNaissance($faker->date('Y-m-d'));
            $user->setRoles(['ROLE_EXPERT']);
            $user->setPassword($this->passwordEncoder->encodePassword($user, 'userpass'));
            $manager->persist($user);
        }

        $manager->flush();
    }
}