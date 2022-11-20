<?php

namespace App\DataFixtures;

use App\Entity\Article;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ArticleFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $article = new Article();
        $article->setTitle('My first news title');
        $article->setDescription('The first news title description for the test application');
        $article->setDate(new \DateTime());
        $article->setPicture('https://techcrunch.com/wp-content/uploads/2018/11/GettyImages-1067784304.jpg?w=430&h=230&crop=1');
        $manager->persist($article);

        $article2 = new Article();
        $article2->setTitle('Look at the 2nd news title');
        $article2->setDescription('After the first title comes this new title with this description');
        $article2->setDate(new \DateTime());
        $article2->setPicture('https://techcrunch.com/wp-content/uploads/2022/11/Impulse_Website-P2_Value-Prop_Controls_r1_3000-Large.jpeg?w=430&h=230&crop=1');
        $manager->persist($article2);
        $manager->flush();
    }
}
