<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Plat;
use App\Entity\Restaurant;
use App\Form\PlatType;
use App\Form\RestaurantType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Route("/restaurant")
 * @Security("is_granted('ROLE_RESTAURATEUR')")
 */
class RestaurantController extends AbstractController
{
    /**
     * @Route("/dashboard", name="restaurant_dashboard")
     */
    public function restaurantDashboard(): Response{
        //Nous faisons appel à l'Entity Manager afin de récupérer les Repository nécessaires
        $entityManager = $this->getDoctrine()->getManager();
        $restaurantRepository = $entityManager->getRepository(Restaurant::class);
        //Nous récupérons la liste des Catégories pour notre aside
        $categoryRepository = $entityManager->getRepository(Category::class);
        $categories = $categoryRepository->findAll();
        //Nous récupérons tous les Restaurants à afficher
        $restaurants = $restaurantRepository->findAll();
        
        //Nous transmettons les variables pertinentes à notre vue Twig via le render()
        return $this->render('restaurant/restaurant-dashboard.html.twig', [
            'categories' => $categories,
            'restaurants' => $restaurants,
        ]);
    }
    
    /**
     * @Route("/dashboard/review/{restaurantId}", name="restaurant_review")
     */
    public function restaurantReview(Request $request, $restaurantId){
        //Cette fonction a pour but de publier la liste des plats d'un Restaurant donné, avec comme option de les modifier ou supprimer
        $entityManager = $this->getDoctrine()->getManager();
        $restaurantRepository = $entityManager->getRepository(Restaurant::class);
        //Nous récupérons la liste des Catégories pour notre aside
        $categoryRepository = $entityManager->getRepository(Category::class);
        $categories = $categoryRepository->findAll();
        //Nous récupérons tous les Restaurants à afficher
        $restaurant = $restaurantRepository->find($restaurantId);
        //Si le restaurant n'est pas trouvé, nous revenons au tableau de bord
        if(!$restaurant){
            return $this->redirect($this->generateUrl('restaurant_dashboard'));
        }
        
        //Nous transmettons les variables pertinentes à notre vue Twig via le render()
        return $this->render('restaurant/restaurant-review.html.twig', [
            'categories' => $categories,
            'restaurant' => $restaurant,
        ]);
    }
    
    /**
     * @Route("/create", name="restaurant_create")
     */
    public function createRestaurant(Request $request): Response
    {
        //Cette route a pour objectif de créer un nouveau Restaurant
        //Nous commençons donc par récupérer l'Entity Manager afin de dialoguer la BDD
        $entityManager = $this->getDoctrine()->getManager();
        //Nous récupérons la liste des Catégories pour notre aside
        $categoryRepository = $entityManager->getRepository(Category::class);
        $categories = $categoryRepository->findAll();
        //Nous créons notre objet Restaurant et le formulaire lié
        $restaurant = new Restaurant;
        $restaurantForm = $this->createForm(RestaurantType::class, $restaurant);
        //Nous appliquons la Request à notre formulaire et si valide, nous persistons $product
        $restaurantForm->handleRequest($request);
        if($request->isMethod('post') && $restaurantForm->isValid()){
            $entityManager->persist($restaurant);
            $entityManager->flush();
            return $this->redirect($this->generateUrl('restaurant_dashboard'));
        }
        //Si le formulaire n'est pas validé, nous le transmettons par la vue
        return $this->render('index/dataform.html.twig', [
            'categories' => $categories,
            'formName' => 'Création de Produit',
            'dataForm' => $restaurantForm->createView(),
        ]);
    }
    
    /**
     * @Route("/update/{restaurantId}", name="restaurant_update")
     */
    public function updateRestaurant(Request $request, $restaurantId = false): Response{
        //Cette fonction nous sert à modifier les informations d'une Entity Restaurant déjà persistée dans notre BDD
        //Ainsi, nous commençons par récupérer l'Entity Manager et les Repository pertinents
        $entityManager = $this->getDoctrine()->getManager();
        $restaurantRepository = $entityManager->getRepository(Restaurant::class);
        //Nous récupérons la liste des Catégories pour notre aside
        $categoryRepository = $entityManager->getRepository(Category::class);
        $categories = $categoryRepository->findAll();
        //Nous récupérons le Restaurant dont l'ID nous a été donnée. Si ce product n'existe pas, nous revenons à l'index
        $restaurant = $restaurantRepository->find($restaurantId);
        if(!$restaurant){
            return $this->redirect($this->generateUrl('restaurant_dashboard'));
        }
        //Le reste de la fonction est identique à createRestaurant --->
        //Si nous avons récupéré un produit, nous le lions à notre nouveau formulaire:
        $restaurantForm = $this->createForm(RestaurantType::class, $restaurant);
        //Nous appliquons la Request à notre formulaire et si valide, nous persistons $product
        $restaurantForm->handleRequest($request);
        if($request->isMethod('post') && $restaurantForm->isValid()){
            $entityManager->persist($restaurant);
            $entityManager->flush();
            return $this->redirect($this->generateUrl('restaurant_dashboard'));
        }
        //Si le formulaire n'est pas validé, nous le transmettons par la vue
        return $this->render('index/dataform.html.twig', [
            'categories' => $categories,
            'formName' => 'Modification de Produit',
            'dataForm' => $restaurantForm->createView(),
        ]);
    }
    
    /**
     * @Route("/delete/{restaurantId}", name="restaurant_delete")
     */
    public function deleteRestaurant(Request $request, $restaurantId = false): Response{
        //Cette fonction supprime le Restaurant que nous avons indiqué de notre base de données avant de revenir au backoffice administrateur
        //Nous commençons par récupérer l'Entity Manager et les Repository nécessaires pour dialoguer avec notre base de données
        $entityManager = $this->getDoctrine()->getManager();
        $restaurantRepository = $entityManager->getRepository(Restaurant::class);
        //Nous récupérons le Restaurant à supprimer selon l'ID indiquée
        $restaurant = $restaurantRepository->find($restaurantId);
        //Si le Restaurant n'existe pas, nous revenons au backoffice
        if(!$restaurant){
            return $this->redirect($this->generateUrl('restaurant_dashboard'));
        }
        //Si nous possédons bien une Entity à supprimer, nous faisons une requête remove() avant de revenir au backoffice
        $entityManager->remove($restaurant);
        $entityManager->flush();
        return $this->redirect($this->generateUrl('restaurant_dashboard'));
    }
    
    /**
     * @Route("/plat/create/{restaurantId}", name="plat_create")
     */
    public function createPlat(Request $request, $restaurantId): Response{
        //Cette fonction a pour objectif d'ajouter un plat au restaurant spécifié via l'URL
        //Nous devons tout d'abord faire appel à l'Entity Manager afin de pouvoir dialoguer avec notre base de données
        $entityManager = $this->getDoctrine()->getManager();
        $restaurantRepository = $entityManager->getRepository(Restaurant::class);
        //Nous récupérons la liste des Restaurants par Catégorie, via le Repository de Category
        $categoryRepository = $entityManager->getRepository(Category::class);
        $categories = $categoryRepository->findAll();
        //Nous récupérons le Restaurant auquel nous allons lier le plat:
        $restaurant = $restaurantRepository->find($restaurantId);
        //Si aucun restaurant n'est trouvé, nous retournons au tableau de bord
        if(!$restaurant){
            return $this->redirect($this->generateUrl('restaurant_dashboard'));
        }
        //Si le Restaurant existe, nous nous chargeons de la génération de notre formulaire
        $plat = new Plat;
        $platForm = $this->createForm(PlatType::class, $plat);
        //Nous nous chargeons de persister les données du formulaire s'il est rempli et valide
        $platForm->handleRequest($request);
        if($request->isMethod('post') && $platForm->isValid()){
            $plat->setRestaurant($restaurant);
            $entityManager->persist($plat);
            $entityManager->flush();
            return $this->redirect($this->generateUrl('restaurant_review', ['restaurantId' => $restaurantId]));
        }
        
        //Nous envoyons notre formulaire à la vue Twig:
        return $this->render('index/dataform.html.twig', [
            'categories' => $categories,
            'formName' => 'Création de Plat',
            'dataForm' => $platForm->createView(),
        ]);
    }
    
    /**
     * @Route("/plat/update/{platId}", name="plat_update")
     */
    public function updatePlat(Request $request, $platId = false): Response{
        //Cette fonction nous sert à modifier les informations d'une Entity Plat déjà persistée dans notre BDD
        //Ainsi, nous commençons par récupérer l'Entity Manager et les Repository pertinents
        $entityManager = $this->getDoctrine()->getManager();
        $platRepository = $entityManager->getRepository(Plat::class);
        //Nous récupérons la liste des Catégories pour notre aside
        $categoryRepository = $entityManager->getRepository(Category::class);
        $categories = $categoryRepository->findAll();
        //Nous récupérons le Plat dont l'ID nous a été donnée. Si ce product n'existe pas, nous revenons à l'index
        $plat = $platRepository->find($platId);
        if(!$plat){
            return $this->redirect($this->generateUrl('restaurant_dashboard'));
        }
        //Le reste de la fonction est identique à createPlat --->
        //Si nous avons récupéré un produit, nous le lions à notre nouveau formulaire:
        $platForm = $this->createForm(PlatType::class, $plat);
        //Nous appliquons la Request à notre formulaire et si valide, nous persistons $plat
        $platForm->handleRequest($request);
        if($request->isMethod('post') && $platForm->isValid()){
            $entityManager->persist($plat);
            $entityManager->flush();
            return $this->redirect($this->generateUrl('restaurant_review', ['restaurantId' => $plat->getRestaurant()->getId()]));
        }
        //Si le formulaire n'est pas validé, nous le transmettons par la vue
        return $this->render('index/dataform.html.twig', [
            'categories' => $categories,
            'formName' => 'Modification de Plat',
            'dataForm' => $platForm->createView(),
        ]);
    }
    
    /**
     * @Route("/plat/delete/{platId}", name="plat_delete")
     */
    public function deletePlat(Request $request, $platId = false): Response{
        //Cette fonction supprime le Plat que nous avons indiqué de notre base de données avant de revenir au backoffice administrateur
        //Nous commençons par récupérer l'Entity Manager et les Repository nécessaires pour dialoguer avec notre base de données
        $entityManager = $this->getDoctrine()->getManager();
        $platRepository = $entityManager->getRepository(Plat::class);
        //Nous récupérons le Restaurant à supprimer selon l'ID indiquée
        $plat = $platRepository->find($platId);
        //Si le Plat n'existe pas, nous revenons au backoffice
        if(!$plat){
            return $this->redirect($this->generateUrl('restaurant_dashboard'));
        }
        //Si nous possédons bien une Entity à supprimer, nous faisons une requête remove() avant de revenir au backoffice
        $entityManager->remove($plat);
        $entityManager->flush();
        return $this->redirect($this->generateUrl('restaurant_review', ['restaurantId' => $plat->getRestaurant()->getId()]));
    }
    
}
