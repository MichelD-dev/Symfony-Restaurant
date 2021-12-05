<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        // $product = new Product();
        // $manager->persist($product);
        
        $categoryList = [
            'Italien',
            'Indien',
            'General',
            'Fast-Food'
        ];
        
        $loremIpsum = [
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse eu mauris in metus vehicula ornare vel ac nulla. Proin dictum libero justo, vel accumsan felis placerat nec. Proin malesuada, metus eget ornare varius, tortor purus posuere justo, vitae euismod magna turpis id lectus. Aenean tempus velit vel feugiat.',
            'In ac ex justo. Ut interdum odio vitae lectus placerat, vitae lacinia nulla tempor. Sed non bibendum augue, et congue felis. Suspendisse fermentum nisl a purus pretium sodales. Curabitur feugiat orci odio, suscipit vehicula sapien posuere quis. Ut id sodales leo. Curabitur sapien justo, tempor at erat id, porta scelerisque nisl.',
            'Maecenas mattis ornare nisl, vel lobortis tortor suscipit non. Integer hendrerit vitae mi nec hendrerit. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Phasellus interdum odio a libero fermentum, vehicula porttitor sapien efficitur. Quisque accumsan metus nec eros aliquet, at blandit leo sodales. ',
            'Nulla ornare est eu egestas semper. Vestibulum purus elit, luctus ultricies justo sed, efficitur hendrerit nibh. In quis turpis a erat semper sodales. Sed nulla diam, fringilla id blandit id, malesuada id risus. Duis sit amet lacus a mi blandit egestas id quis risus. Phasellus vitae dictum libero. Morbi sapien nibh, dapibus quis ullamcorper eget, imperdiet sit amet tortor.',
            ' Ut quis velit vel ligula posuere cursus. Aenean non elit at justo malesuada placerat. Maecenas at porta mauris, dignissim finibus lectus. Curabitur ac libero sapien. Aliquam vulputate risus ultricies tellus dapibus, at scelerisque ex interdum.',
            ' Fusce laoreet erat ligula, vel eleifend turpis dictum ac. Donec porta sed lectus et ultricies. Donec porttitor elementum rhoncus. Aenean vulputate lobortis ligula, at fermentum elit molestie eget. In sodales metus neque, in pulvinar diam fermentum porttitor. Quisque tempus turpis et placerat dignissim. Duis in tristique turpis. Etiam dapibus ante id elit accumsan mattis. Etiam ut mollis neque, eu faucibus sapien. ',
            'Nullam elit eros, aliquam a convallis sit amet, malesuada eget leo. Quisque in sagittis ipsum. Sed ac mauris at nisl laoreet auctor eget et purus. Praesent at sollicitudin justo, nec tempus eros. Vivamus eget bibendum est. Vivamus sollicitudin, neque vitae auctor tempus, ante arcu consectetur purus, sed porta urna sem eu dui.',
            'Quisque et sapien aliquam, fermentum dolor quis, fringilla erat. In cursus imperdiet quam vitae vulputate. Vivamus laoreet orci in metus hendrerit, quis aliquet nisi bibendum. Aenean at elementum tellus, vitae pulvinar mauris. Aenean luctus orci nec quam rutrum viverra. Integer semper ex quis pretium iaculis. Phasellus volutpat augue ut risus feugiat, at cursus nisi interdum. ',
        ];

        //Préparation des boucles foreach
        
        foreach($categoryList as $categoryName){
            $category = new \App\Entity\Category;
            $category->setName($categoryName);
            $category->setDescription($loremIpsum[rand(0, (count($loremIpsum) - 1))]);
            
            for($i = 0; $i < rand(4,8); $i++){ //Nous préparons autant de restaurants que de cycles
                $restaurant = new \App\Entity\Restaurant;
                $restaurant->setName('Restaurant ' . $categoryName . ' #' . rand(0,999));
                $restaurant->setDescription($loremIpsum[rand(0, (count($loremIpsum) - 1))]);
                $restaurant->setOpenHours('Du Lundi au Samedi, 9h -> 22h');
                $restaurant->setAddress('15 avenue des Cerisiers');
                $restaurant->setCategory($category);
                //Nous créons une nouvelle boucle for pour lier des plats au Restaurant:
                for($j = 0; $j < rand(5, 15); $j++){
                    $plat = new \App\Entity\Plat;
                    $plat->setName('Plat #' . rand(0,999));
                    $plat->setDescription($loremIpsum[rand(0, (count($loremIpsum) - 1))]);
                    $plat->setPrice(rand(5, 20) + 0.99);
                    $plat->setRestaurant($restaurant);
                    //Nous appliquons notre demande de persistance de notre plat
                    $manager->persist($plat);
                }
                //Nous appliquons notre demande de persistance de notre restaurant
                $manager->persist($restaurant);
            }
            $manager->persist($category);
        }
        
        $manager->flush();
    }
}
