<?php
/*
  Plugin Name: Moteur recherche batteries
  Plugin URI: na
  Description: Un plugin permettant d'ajouter un moteur de recherche personalisé pour woocommerce
  Version: 0.1
  Author: na
  Author URI: #
  License: GPL2
 */


/*
 * Le shortcode pour afficher le formulaire 
 */

function add_moteur_shortcode() {
    ob_start();
    ?>
    <style>
        
        .mrb_groupe {
            width: 45%;
            margin-bottom:20px;
            display: inline-block;
            
            
        }
        select {
            width:100%;
        }
        select:disabled {
            opacity: .6;
        }
    </style>
    <h1>Trouver votre batterie pour votre véhicule</h1>
    <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">

        <div class="mrb_groupe">
            <label>Constructeur</label>
            <select id="mrb_marques" name="marques">
                <option value="">Choisissez</option>
            </select>
        </div>

        <div class="mrb_groupe">
            <label>Modèle du véhicule</label>
            <select id="mrb_modeles" name="modeles" disabled>
                <option value="">Choisissez</option>
            </select>
        </div>

        <div class="mrb_groupe">
            <label>Motorisation</label>
            <select id="mrb_motorisations" name="motorisations" disabled>
                <option value="">Choisissez</option>
            </select>
        </div>

        <div class="mrb_groupe">
            <label>Année</label>
            <select id="mrb_annees" name="annees" disabled>
                <option value="">Choisissez</option>
            </select>
        </div>

        <!--
        <label>Année</label>
        <select id="mrb_annees" ></select> !-->
        <input type="hidden" name="s" value="" />
        <input type="hidden" name="post_type" value="product" />
        <input type="submit" value="Trouvez votre batterie">

    </form>

    <?php
    return ob_get_clean();
}

add_shortcode('mrb', 'add_moteur_shortcode');

/**
 * Déclare le script js à utiliser
 * Merci http://www.geekpress.fr/tuto-ajax-wordpress-methode-simple/
 */
function add_js_scripts() {
    wp_enqueue_script('mrb_script', plugins_url() . '/moteur-recherche-batteries/mrb_script.js', array('jquery'), '1.0', true);
    wp_localize_script('mrb_script', 'ajax_url', admin_url('admin-ajax.php')); /* Permet d'accéder au script 'admin ajax' via le js */
}

add_action('wp_enqueue_scripts', 'add_js_scripts');


/**
 * Le script php qui sera appelé en ajax
 * On peut récupérer la marque, modele et motorisation à la demande
 */
add_action('wp_ajax_get_batteries', 'get_batteries');
add_action('wp_ajax_nopriv_get_batteries', 'get_batteries'); /* Permet de faire fonctionner pour les admin */

function get_batteries() {
    global $wpdb;

    /* La requete demandé depuis Javascript */
    $requete = $_POST['data']['requete'];

    /* Objet contenant les choix de l'utilisateur */
    $selected_values_data = $_POST['data']['selectedValues'];
    $selected_values = json_decode(stripslashes(html_entity_decode($selected_values_data)));

    /* Il faut sécuriser les query absoluement : https://codex.wordpress.org/Class_Reference/wpdb#Protect_Queries_Against_SQL_Injection_Attacks */
    switch ($requete) {
        case 'get_marques' :
            $results = $wpdb->get_col("SELECT DISTINCT Marque FROM vehicules");
            break;

        case 'get_modeles':
            $results = $wpdb->get_col("SELECT DISTINCT `Modèle` FROM `vehicules` WHERE `Marque` = '$selected_values->marques' ");
            break;

        case 'get_motorisations':
            /* On veut pouvoir s'assurer qu'il n'y ait pas récupération de doublon. Ex une motorisation identique pour deux marques différente */
            $results = $wpdb->get_col("SELECT DISTINCT `Motorisation` FROM `vehicules` WHERE `Marque` = '$selected_values->marques' AND `Modèle` = '$selected_values->modeles' ");
            break;

        case 'get_annees':
            $results = array();
            $range_annees = $wpdb->get_results("SELECT `De`, `A`  FROM `vehicules` WHERE `Marque` = '$selected_values->marques' AND `Modèle` = '$selected_values->modeles' AND `Motorisation` = '$selected_values->motorisations' ", ARRAY_N);
            foreach ($range_annees as $range_annee) {
                $separator = (!in_array("", $range_annee)) ? "-" : ""; //* On définis le séparateur "-" si le tableau n'a pas de valeur vide*/
                $results[] = implode($separator, $range_annee);  //On fussionne le tableau*/
            }
            break;
    }
    echo json_encode($results);
    die();
}

/*
 * Permet de modifier le comportement natif des recherches afin d'obtenir le résultat souhaité
 */
function _additional_woo_query($query) {
    if ($query->is_search) {

        $marque = $_GET['marques'];
        $modele = $_GET['modeles'];
        $motorisation = $_GET['motorisations'];

        /* Recupération des SKU (UGS) à partir de l'url */
        global $wpdb;
        $skus = $wpdb->get_results("SELECT `AGM / AFB`, `ULTRA`, `ENERGY +` , `BAREN` FROM `vehicules` WHERE `Marque`='$marque' AND `Modèle`='$modele' AND `Motorisation`= '$motorisation'", ARRAY_N);
        $meta_query = $query->get('meta_query');

        $meta_query[] = array(
            'key' => '_sku',
            /* 'value'   => array('PV1110', 'PV10', '7904143'), */
            'value' => $skus[0],
            'compare' => 'IN',
        );
        $query->set('meta_query', $meta_query);
    }
}
add_action('pre_get_posts', '_additional_woo_query');

