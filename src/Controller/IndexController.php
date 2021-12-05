<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Commande;
use App\Entity\Plat;
use App\Entity\Reservation;
use App\Entity\Restaurant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class IndexController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index(): Response
    {
        //Nous récupérons notre Entity Manager et la liste des Category
        $entityManager = $this->getDoctrine()->getManager();
        $categoryRepository = $entityManager->getRepository(Category::class);
        $restaurantRepository = $entityManager->getRepository(Restaurant::class);
        //Nous récupérons la totalité des Catégories afin de les transmettre à Twig
        $categories = $categoryRepository->findAll();
        //Nous récupérons la liste de tous les Restaurants afin d'en présenter une sélection au sein de notre page d'accueil
        $restaurants = $restaurantRepository->findAll();
        //Parmi tous les restaurants, nous en sélectionnons 8 que nous transmettons à la vue via un tableau de quatre paires d'Entity Restaurant
        shuffle($restaurants); //Nous mélangeons la liste des Restaurants
        $restaurantList = [];
        for($i = 0; $i < 4; $i++){ 
            //Nous créons un tableau de deux Entity que nous attachons à notre tableau
            //Ainsi, nous finissons notre boucle avec un tableau de 4 paires de deux tableaux
            $restaurantPair = [];
            array_push($restaurantPair, $restaurants[$i], $restaurants[$i+4]);
            array_push($restaurantList, $restaurantPair);
        }
        //Nous transmettons nos variables au template Twig de notre choix (index)
        return $this->render('index/index.html.twig', [
            'categories' => $categories,
            'restaurantList' => $restaurantList,
        ]);
    }
    
    /**
     * @Route("/display/{restaurantId}", name="index_restaurant")
     */
    public function indexRestaurant(Request $request, $restaurantId): Response
    {
        //Nous récupérons notre Entity Manager et la liste des Category
        $entityManager = $this->getDoctrine()->getManager();
        $categoryRepository = $entityManager->getRepository(Category::class);
        $restaurantRepository = $entityManager->getRepository(Restaurant::class);
        $commandeRepository = $entityManager->getRepository(Commande::class);
        //Nous récupérons l'Utilisateur en cours et sa possible commande sur ce Restaurant
        $user = $this->getUser();
        $commande = null;
        if($user){
            $commande = $commandeRepository->findBy(['client' => $user, 'status' => 'panier']);
            if($commande){
                $commande = $commande[0];
            }
        }
        //Nous récupérons la totalité des Catégories afin de les transmettre à Twig
        $categories = $categoryRepository->findAll();
        //Nous récupérons le Restaurant qui nous intéresse:
        $restaurant = $restaurantRepository->find($restaurantId);
        //Si le Restaurant n'existe pas, nous retournons à l'index
        if(!$restaurant){
            return $this->redirect($this->generateUrl('index'));
        }
        //Nous transmettons nos variables au template Twig de notre choix (index)
        return $this->render('index/index-restaurant.html.twig', [
            'commande' => $commande,
            'categories' => $categories,
            'restaurant' => $restaurant,
        ]);
    }
    
    /**
     * @Route("/buy/add/{platId}", name="reservation_add")
     * @Security("is_granted('ROLE_CLIENT')")
     */
    public function addReservation(Request $request, $platId): Response{
        //Cette méthode de Controller ajoute à la commande d'un certain restaurant un exemplaire du plat visé
        //Nous devons tout d'abord faire appel à notre Entity Manager pour dialoguer avec notre BDD
        $user = $this->getUser(); //Nous récupérons l'Utilisateur connecté
        $entityManager = $this->getDoctrine()->getManager();
        $platRepository = $entityManager->getRepository(Plat::class);
        $commandeRepository = $entityManager->getRepository(Commande::class);
        //Nous récupérons le plat indiqué par l'URL.
        $plat = $platRepository->find($platId);
        //Si le plat n'existe pas, nous revenons à l'index
        if(!$plat){
            return $this->redirect($this->generateUrl('index'));
        }
        $restaurant = $plat->getRestaurant(); //Nous plaçons le Restaurant du plat dans sa propre variable
        //Nous commençons par vérifier s'il existe une commande avec un statut actif qui concerne ce restaurant
        $commandes = $commandeRepository->findBy(['restaurant' => $plat->getRestaurant(), 'client' => $user, 'status' => 'panier']);
        if(empty($commandes)){ //Si aucune commande ne correspond
            $commande = new Commande(); //Je crée une nouvelle commande relative à ce restaurant
            $commande->setRestaurant($restaurant);
            $commande->setClient($user);
        } else $commande = $commandes[0]; //Je récupère la commande en tête de la pile
        $reservation = false; //Nous initialisons Reservation
        //Si un plat d'une Reservation présente dans notre Commande est la même que le plat de notre méthode de controller, nous récupérons cette Reservation sous notre variable $reservation
        foreach($commande->getReservations() as $commandeReservation){
            if($commandeReservation->getPlat() == $plat){ 
                $reservation = $commandeReservation;
                break;
            }
        }
        //Si aucune Reservation de ce Plat, dans cette Commande, n'existe
        if(!$reservation){
            //Nous créons notre Reservation et nous l'ajoutons au plat sélectionné
            $reservation = new Reservation;
            $reservation->setQuantity(1);
            $reservation->setPlat($plat);
            $reservation->setCommande($commande);
        } else {
            //Nous incrémentons la $quantity de notre Reservation de 1
            $reservation->setQuantity($reservation->getQuantity() + 1);
        }
        //A présent que notre Réservation est complètement renseignée, nous pouvons la faire persister:
        $entityManager->persist($reservation);
        $entityManager->persist($commande);
        $entityManager->flush();
        //Nous revenons à la page du Restaurant 
        return $this->redirect($this->generateUrl('index_restaurant', ['restaurantId' => $plat->getRestaurant()->getId()]));
    }
    
    /**
     * @Route("/buy/remove/{platId}", name="reservation_remove")
     * @Security("is_granted('ROLE_CLIENT')")
     */
    public function removeReservation(Request $request, $platId = false): Response{
        $user = $this->getUser();
        //Cette méthode de Controller a pour objectif de décrémenter ou de supprimer une Réservation effectuée précédemment dans une Commande liée à ce Restaurant précis
        //Nous récupérons l'Entity Manager afin de pouvoir dialoguer avec notre base de données
        $entityManager = $this->getDoctrine()->getManager();
        $platRepository = $entityManager->getRepository(Plat::class);
        $commandeRepository = $entityManager->getRepository(Commande::class);
        //Nous récupérons le plat indiqué par notre paramètre de route
        $plat = $platRepository->find($platId);
        //Si le plat n'existe pas, nous revenons à notre page d'index
        if(!$plat){
            return $this->redirect($this->generateUrl('index'));
        }
        //Si le plat existe, nous récupérons le Restaurant lié
        $restaurant = $plat->getRestaurant();
        //Nous vérifions s'il existe une commande liée à ce Restaurant avec un statut 'panier'
        $commandes = $commandeRepository->findBy(['restaurant' => $plat->getRestaurant(), 'client' => $user, 'status' => 'panier']);
        //Si la commande n'existe pas, la Reservation non et nous revenons donc à l'accueil du Restaurant
        if(empty($commandes)){
            return $this->redirect($this->generateUrl('index_restaurant', ['restaurantId' => $restaurant->getId()]));
        }
        //Si la Commande existe, nous la tirons de son tableau et nous recherchons s'il existe une Reservation au nom du plat lié
        $commande = $commandes[0];
        $reservation = false; //Nous initialisons Reservation
        //Si un plat d'une Reservation présente dans notre Commande est la même que le plat de notre méthode de controller, nous récupérons cette Reservation sous notre variable $reservation
        foreach($commande->getReservations() as $commandeReservation){
            if($commandeReservation->getPlat() == $plat){ 
                $reservation = $commandeReservation;
                break;
            }
        }
        //Si la Reservation n'existe pas, nous revenons à notre page de Restaurant
        if(!$reservation){
            return $this->redirect($this->generateUrl('index_restaurant', ['restaurantId' => $restaurant->getId()]));
        } else {
            //Nous vérifions la quantity de la Réservation. Si elle est égale ou inférieure à 0 après décrémentation, nous supprimons la Réservation
            $reservation->setQuantity($reservation->getQuantity() - 1);
            if($reservation->getQuantity() <= 0){
                $commande->removeReservation($reservation); //Nous retirons la Reservation de la Commande
                $entityManager->remove($reservation); //Requête de suppression de la Reservation
            } else $entityManager->persist($reservation);
        }
        //Si la dernière réservation de la Commande a été supprimée, nous supprimons également la Commande
        if($commande->getReservations()->isEmpty()){ //Fonction ArrayCollection vérifiant si le tableau est vide
            $entityManager->remove($commande);
        }
        $entityManager->flush();
        //Après le processus de décrémentation/suppression, nous revenons à l'accueil du Restaurant
        return $this->redirect($this->generateUrl('index_restaurant', ['restaurantId' => $restaurant->getId()]));
    }
    
    /**
     * @Route("/buy/validate/{restaurantId}", name="commande_validate")
     * @Security("is_granted('ROLE_CLIENT')")
     */
    public function validateCommande(Request $request, $restaurantId): Response{
        $user = $this->getUser();
        //Cette fonction valide la Commande en cours dans ce restaurant
        //Nous récupérons l'Entity Manager et le Repository de Restaurant
        $entityManager = $this->getDoctrine()->getManager();
        $restaurantRepository = $entityManager->getRepository(Restaurant::class);
        //Nous récupérons le Restaurant indiqué
        $restaurant = $restaurantRepository->find($restaurantId);
        //Si le restaurant n'existe pas, nous retournons à l'index
        if(!$restaurant){
            return $this->redirect($this->generateUrl('index'));
        }
        //Si le Restaurant existe, nous vérifions s'il existe une Commande en mode panier en son nom, et si oui, nous la validons
        $commande = false;
        foreach($restaurant->getCommandes() as $restaurantCommande){
            if(($restaurantCommande->getStatus() == 'panier') && ($restaurantCommande->getClient() == $user)){
                $commande = $restaurantCommande;
                break;
            }
        }
        //Si la commande est trouvée, nous modifions son statut
        if($commande){
            $commande->setStatus('validee');
            $entityManager->persist($commande);
            $entityManager->flush();
            //Nous créons un flash bag notifiant la validation de la commande
            $request->getSession()->getFlashBag()->add('message', 'Votre commande a bien été validée');
        }
        //Nous revenons alors vers la vitrine de notre Restaurant
        return $this->redirect($this->generateUrl('index_restaurant', ['restaurantId' => $restaurant->getId()]));
    }

}
