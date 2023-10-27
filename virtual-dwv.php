<?php
/**
 * Plugin Name: Virtual DWV
 * Description: Descrição do meu plugin.
 * Version: 1.0.0
 * Author: Raphael Reis
 * Author URI: https://exemplo.com/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Seu código começa aqui
// Inclua o arquivo custom-fields.php

require_once(plugin_dir_path(__FILE__) . 'custom-fields.php');
require_once(plugin_dir_path(__FILE__) . 'custom-post-type.php');
require_once plugin_dir_path(__FILE__) . '/inc/dmv-integration-ajax-sync.php';
require_once plugin_dir_path(__FILE__) . '/inc/log-file.php';

// Adicione as configurações de autenticação da API da DWV
define('DWV_API_ENDPOINT', get_option('dwv_integration_url', ''));
define('DWV_API_TOKEN', get_option('dwv_integration_token', ''));

// Adicione a função dwv_integration_get_imoveis se não existir
if (!function_exists('dwv_integration_get_imoveis')) {
    function dwv_integration_get_imoveis()
    {
        // Configurações da API
        $endpoint = DWV_API_ENDPOINT . '/integration/properties';
        $token = DWV_API_TOKEN;

        // Cabeçalho com o token de autenticação
        $headers = array(
            'Authorization' => 'Bearer ' . $token
        );

        // Faz a requisição à API
        $response = wp_remote_get($endpoint, array(
            'headers' => $headers
        ));

        // Verifica se a requisição foi bem-sucedida
        if (is_wp_error($response)) {
            return false;
        }

        // Processa a resposta da API
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // Retorna os imóveis
        return $data['data'];
    }
}

// Adiciona a página de administração
function dwv_integration_add_admin_page()
{
    add_menu_page(
        'DWV Integration',
        'DWV Integration',
        'manage_options',
        'dwv-integration',
        'dwv_integration_admin_page',
        'dashicons-admin-generic',
        20
    );
}
add_action('admin_menu', 'dwv_integration_add_admin_page');

// Callback da página de administração
function dwv_integration_admin_page()
{
    // Verifica se o usuário tem permissão para acessar a página de administração
    if (!current_user_can('manage_options')) {
        return;
    }

    // Processa o envio do formulário (se houver)
    if (isset($_POST['dwv_integration_settings'])) {
        $token = sanitize_text_field($_POST['dwv_integration_token']);
        $url = sanitize_text_field($_POST['dwv_integration_url']);

        update_option('dwv_integration_token', $token);
        update_option('dwv_integration_url', $url);
    }

    // Obtém as configurações atuais
    $current_token = get_option('dwv_integration_token', '');
    $current_url = get_option('dwv_integration_url', '');

    ?>
    <div class="wrap">
        <h1>DWV Integration</h1>
        <form method="post" action="">
            <h2>Configurações</h2>
            <label for="dwv_integration_token">Token de Autenticação:</label>
            <input type="text" name="dwv_integration_token" id="dwv_integration_token" value="<?php echo esc_attr($current_token); ?>">
            
            <label for="dwv_integration_url">URL da API:</label>
            <input type="text" name="dwv_integration_url" id="dwv_integration_url" value="<?php echo esc_attr($current_url); ?>">

            <?php submit_button('Salvar Configurações', 'primary', 'dwv_integration_settings'); ?>
        </form>

        <hr>

        <div class="lastUpdateInfos">
            <span class="lastUpdateDate"></span>
            <!-- <span class="prog">26 imoveis atualizados, 5 excluídos</span> -->
        </div>
        <h2>Sincronização manual</h2>
        <p>Clique no botão abaixo para sincronizar agora manualmente:</p>
        <button id="syncImoveis" class="btn btn-warning">Sincronizar Agora</button>
        <div class="progress-showing">
            <div class="spinnerLoading">
                <div class="spinnerA"></div>
            </div>
            <div>
                <h3 class="progress-label"></h3>
                <p class="textWarning">Isto pode levar um tempo volte quando quiser para ver se terminamos, aguarde nesta tela até o fim do processo.</p>
            </div>
        </div>
        <!-- <form method="post" action="">
            "<submit_button('Sincronizar Agora', 'secondary', 'dwv_integration_manual_sync'); >"
        </form> -->

        <hr>

        <h2>Testar Conexão</h2>
        <form method="post" action="">
            <?php submit_button('Testar Conexão', 'secondary', 'dwv_integration_test_connection'); ?>
        </form>
        <?php
        if (isset($_POST['dwv_integration_test_connection'])) {
            $connection_status = dwv_integration_test_connection();
            ?>
            <p style="color:<?php echo $connection_status['color']; ?>"><?php echo $connection_status['message']; ?></p>
            <?php
        }
        ?>
    </div>
    <?php
}

// Callback para o envio do formulário e sincronização manual
// function dwv_integration_admin_actions()
// {
//     if (isset($_POST['dwv_integration_manual_sync'])) {
//         dwv_integration_sync_now();
//     }
// }
// add_action('admin_init', 'dwv_integration_admin_actions');

// Função para sincronizar agora manualmente
// function dwv_integration_sync_now()
// {
//     // Execute a sincronização imediatamente
//     dwv_integration_sync_daily();

//     // Redirecione de volta para a página de administração após a sincronização
//     wp_redirect(admin_url('admin.php?page=dwv-integration'));
//     exit;
// }

// Função para testar a conexão com a API
function dwv_integration_test_connection()
{
    $token = get_option('dwv_integration_token', '');
    $url = get_option('dwv_integration_url', '');

    // Verifica se as configurações estão preenchidas
    if (empty($token) || empty($url)) {
        return array(
            'message' => 'Erro: As configurações estão incompletas.',
            'color' => 'red'
        );
    }

    // Configurações da API
    $endpoint = $url . '/integration/properties';
    $headers = array(
        'Authorization' => 'Bearer ' . $token
    );

    // Faz uma requisição de teste à API
    $response = wp_remote_get($endpoint, array(
        'headers' => $headers
    ));

    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        return array(
            'message' => 'A conexão com a API foi bem-sucedida.',
            'color' => 'green'
        );
    } else {
        return array(
            'message' => 'Erro: Não foi possível estabelecer conexão com a API.',
            'color' => 'red'
        );
    }
}

// Função para sincronizar diariamente
function dwv_integration_sync_daily()
{
    // Consulta à API da DWV
    $imoveis = dwv_integration_get_imoveis();

    // Verifica se há imóveis retornados pela API
    if (!empty($imoveis)) {
        foreach ($imoveis as $imovel) {
            // Verifica se o imóvel já existe no WordPress
            $existing_post = get_page_by_title($imovel['title'], OBJECT, 'imovel');

            if ($existing_post) {
                // Extrai a data de atualização do imóvel existente
                $existing_last_updated_at = get_post_meta($existing_post->ID, 'last_updated_at', true);
        
                // Verifica se a data de atualização do imóvel existente é menor do que a nova data de atualização
                if ($existing_last_updated_at && strtotime($existing_last_updated_at) >= strtotime($imovel['last_updated_at'])) {
                    continue; // Pula para o próximo imóvel se a data existente for maior ou igual
                }

            // Extrai o  a ultima atualização
            $constructionStage = isset($imovel['construction_stage']) ? $imovel['construction_stage'] : null;

            // Extrai o  a ultima atualização
            $last_updated_at = isset($imovel['last_updated_at']) ? $imovel['last_updated_at'] : null;

            // Extrai o ID da unidade do apartamento
            $apartmentUnitId = isset($imovel['unit']['id']) ? $imovel['unit']['id'] : null;

            // Extrai o título do apartamento
            $apartmentTitle = isset($imovel['unit']['title']) ? $imovel['unit']['title'] : null;

            // Extrai o preço do apartamento
            $apartmentPrice = isset($imovel['unit']['price']) ? $imovel['unit']['price'] : null;

            // Extrai o tipo do apartamento
            $apartmentType = isset($imovel['unit']['type']) ? $imovel['unit']['type'] : null;

            // Extrai o número de vagas de garagem do apartamento
            $apartmentParkingSpaces = isset($imovel['unit']['parking_spaces']) ? $imovel['unit']['parking_spaces'] : null;

            // Extrai o número de quartos do apartamento
            $apartmentBedrooms = isset($imovel['unit']['dorms']) ? $imovel['unit']['dorms'] : null;

            // Extrai o número de suítes do apartamento
            $apartmentSuites = isset($imovel['unit']['suites']) ? $imovel['unit']['suites'] : null;

            // Extrai o número de banheiros do apartamento
            $apartmentBathrooms = isset($imovel['unit']['bathroom']) ? $imovel['unit']['bathroom'] : null;

            // Extrai a área privada do apartamento
            $apartmentPrivateArea = isset($imovel['unit']['private_area']) ? $imovel['unit']['private_area'] : null;

            // Extrai a área útil do apartamento
            $apartmentUtilArea = isset($imovel['unit']['util_area']) ? $imovel['unit']['util_area'] : null;

            // Extrai a área total do apartamento
            $apartmentTotalArea = isset($imovel['unit']['total_area']) ? $imovel['unit']['total_area'] : null;

            // // Agora você tem um array de URLs das galerias adicionais
            // $apartmentAdditionalGalleries = isset($imovel['unit']['additional_galleries']) ? $imovel['unit']['additional_galleries'] : null;
            // $processedGalleryAdditional = [];
            
            // if ($apartmentAdditionalGalleries) {
            //     foreach ($apartmentAdditionalGalleries as $gallery) {
            //         if (isset($gallery['files']) && is_array($gallery['files'])) {
            //             foreach ($gallery['files'] as $file) {
            //                 if (isset($file['url'])) {
            //                     $url = $file['url'];
            //                     $processedGalleryAdditional[] = $url;
            //                 }
            //             }
            //         }
            //     }
            // }
            
            // Building Começa aqui 

            $buildingId = isset($imovel['building']['id']) ? $imovel['building']['id'] : null;
            $buildingTitle = isset($imovel['building']['title']) ? $imovel['building']['title'] : null;
            // $buildingGallery = isset($imovel['building']['gallery']) ? $imovel['building']['gallery'] : null;
            // $processedGallery = [];

            // if ($buildingGallery) {
            //     foreach ($buildingGallery as $image) {
            //         if (isset($image['url'])) {
            //             $url = $image['url'];
            //             $file_array = array(
            //                 'name' => basename($url),
            //                 'tmp_name' => download_url($url)
            //             );

            //             // $error_message = json_encode($file_array);
            //             // error_log($error_message);
                        
            //             if (!is_wp_error($file_array['tmp_name'])) {
            //                 $attachment_id = media_handle_sideload($file_array, 0);
            //                 error_log(json_encode($imovel["title"]. " " . $attachment_id));
                            
            //                 if (!is_wp_error($attachment_id)) {
            //                     $processedGallery[] = $attachment_id;
            //                 }
            //             } else {
            //                 // Lida com o erro de download de imagem
            //                 $error_message = $file_array['tmp_name']->get_error_message();

            //                 error_log(json_encode($imovel["title"] . $error_message));
            //             }
            //         }
            //     }
            // }
            
            // if (!empty($processedGallery)) {
            //     update_field('field_building_gallery', $processedGallery, $post_id);
            //     error_log("supostamente adicionado imagens ao campo acf");
            // }
                    
            $architecturalPlans = isset($imovel['building']['architectural_plans']) ? $imovel['building']['architectural_plans'] : null;
            
            if ($architecturalPlans) {
                $planUrls = [];
            
                foreach ($architecturalPlans as $plan) {
                    if (isset($plan['url'])) {
                        $planUrls[] = $plan['url'];
                    }
                }
            
                // Agora, a variável $planUrls conterá os URLs dos planos arquitetônicos do edifício.
            }
            
            $buildingVideo = isset($imovel['building']['video']) ? $imovel['building']['video'] : null;
            
            // O campo "video" conterá o URL do vídeo do edifício, caso exista.
            
            $buildingTour360 = isset($imovel['building']['tour_360']) ? $imovel['building']['tour_360'] : null;
            
            // O campo "tour_360" conterá o URL do tour em 360º do edifício, caso exista.
            
            $buildingDescription = isset($imovel['building']['description']) ? $imovel['building']['description'] : null;
            $descriptionTitle = null;
            $descriptionItems = null;
            
            if ($buildingDescription !== null && isset($buildingDescription[0]['title'])) {
                $descriptionTitle = $buildingDescription[0]['title'];
            
                if (isset($buildingDescription[0]['items']) && is_array($buildingDescription[0]['items'])) {
                    $descriptionItems = $buildingDescription[0]['items'];
                }
            }
            
            $buildingAddress = null;
                    
            // Extrai endereço do building
            $address = isset($imovel['building']['address']) ? $imovel['building']['address'] : null;
            $streetName = isset($imovel['building']['address']['street_name']) ? $imovel['building']['address']['street_name'] : null;
            $streetNumber = isset($imovel['building']['address']['street_number']) ? $imovel['building']['address']['street_number'] : null;
            $neighborhood = isset($imovel['building']['address']['neighborhood']) ? $imovel['building']['address']['neighborhood'] : null;
            $complement = isset($imovel['building']['address']['complement']) ? $imovel['building']['address']['complement'] : null;
            $zipCode = isset($imovel['building']['address']['zip_code']) ? $imovel['building']['address']['zip_code'] : null;
            $city = isset($imovel['building']['address']['city']) ? $imovel['building']['address']['city'] : null;              
            $state = isset($imovel['building']['address']['state']) ? $imovel['building']['address']['state'] : null;
            $country = isset($imovel['building']['address']['country']) ? $imovel['building']['address']['country'] : null;
            $latitude = isset($imovel['building']['address']['latitude']) ? $imovel['building']['address']['latitude'] : null;
            $longitude = isset($imovel['building']['address']['longitude']) ? $imovel['building']['address']['longitude'] : null;
        
                        
            // O campo "address" conterá os detalhes do endereço do edifício.
            
            $buildingTextAddress = isset($imovel['building']['text_address']) ? $imovel['building']['text_address'] : null;
            
            // O campo "text_address" conterá o endereço formatado do edifício.
            
            $buildingIncorporation = isset($imovel['building']['incorporation']) ? $imovel['building']['incorporation'] : null;
            
            // O campo "incorporation" conterá informações sobre a incorporação do edifício.
            
            $buildingCover = isset($imovel['building']['cover']) ? $imovel['building']['cover'] : null;
            $coverUrl = null;
            
            if ($buildingCover && isset($buildingCover['url'])) {
                $coverUrl = $buildingCover['url'];
            }
            
            // Agora, a variável $coverUrl conterá a URL da imagem de capa do edifício, caso exista.
            


            $buildingFeatures = isset($imovel['building']['features']) ? $imovel['building']['features'] : null;

            if ($buildingFeatures) {
                $featureTags = [];
                $featureTypes = [];

                foreach ($buildingFeatures as $feature) {
                    if (isset($feature['tags']) && is_array($feature['tags'])) {
                        $featureTags = array_merge($featureTags, $feature['tags']);
                    }

                    if (isset($feature['type'])) {
                        $featureTypes[] = $feature['type'];
                    }
                }

                // Agora, a variável $featureTags conterá todas as tags das features do edifício.
                // E a variável $featureTypes conterá todos os tipos das features do edifício.
            }

            // O campo "delivery_date" conterá a data de entrega do edifício.
            
            $buildingDeliveryDate = isset($imovel['building']['delivery_date']) ? $imovel['building']['delivery_date'] : null;
        
            // Atualiza os metadados do imóvel existente
            update_post_meta($existing_post->ID, 'id', $imovel['id']);
            update_post_meta($existing_post->ID, 'description', $imovel['description']);
            update_post_meta($existing_post->ID, 'construction_stage', $constructionStage);
            update_post_meta($existing_post->ID, 'last_updated_at', $last_updated_at);

            // Metadados do apartamento
            update_post_meta($existing_post->ID, 'apartment_unit_id', $apartmentUnitId);
            update_post_meta($existing_post->ID, 'apartment_title', $apartmentTitle);
            update_post_meta($existing_post->ID, 'apartment_price', $apartmentPrice);
            update_post_meta($existing_post->ID, 'apartment_type', $apartmentType);
            update_post_meta($existing_post->ID, 'apartment_parking_spaces', $apartmentParkingSpaces);
            update_post_meta($existing_post->ID, 'apartment_bedrooms', $apartmentBedrooms);
            update_post_meta($existing_post->ID, 'apartment_suites', $apartmentSuites);
            update_post_meta($existing_post->ID, 'apartment_bathrooms', $apartmentBathrooms);
            update_post_meta($existing_post->ID, 'apartment_private_area', $apartmentPrivateArea);
            update_post_meta($existing_post->ID, 'apartment_util_area', $apartmentUtilArea);
            update_post_meta($existing_post->ID, 'apartment_total_area', $apartmentTotalArea);
            // update_post_meta($existing_post->ID, 'apartment_additional_galleries', $processedGalleryAdditional);

            // Metadados do empreendimento
            update_post_meta($existing_post->ID, 'building_id', $buildingId);
            update_post_meta($existing_post->ID, 'building_title', $buildingTitle);
            // update_post_meta($existing_post->ID, 'building_gallery', $processedGallery);
            update_post_meta($existing_post->ID, 'building_architectural_plans', $planUrls);
            update_post_meta($existing_post->ID, 'building_video', $buildingVideo);
            update_post_meta($existing_post->ID, 'building_tour_360', $buildingTour360);
            update_post_meta($existing_post->ID, 'building_description_title', $descriptionTitle);
            update_post_meta($existing_post->ID, 'building_description_items', $descriptionItems);
            update_post_meta($existing_post->ID, 'building_address_street_name', $streetName);
            update_post_meta($existing_post->ID, 'building_address_street_number', $streetNumber);
            update_post_meta($existing_post->ID, 'building_address_neighborhood', $neighborhood);
            update_post_meta($existing_post->ID, 'building_address_complement', $complement);
            update_post_meta($existing_post->ID, 'building_address_zip_code', $zipCode);
            update_post_meta($existing_post->ID, 'building_address_city', $city);
            update_post_meta($existing_post->ID, 'building_address_state', $state);
            update_post_meta($existing_post->ID, 'building_address_country', $country);
            update_post_meta($existing_post->ID, 'building_address_latitude', $latitude);
            update_post_meta($existing_post->ID, 'building_address_longitude', $longitude);
            update_post_meta($existing_post->ID, 'building_text_address', $buildingTextAddress);
        }else {
            // Cria um novo post do tipo 'imovel'
            $new_post = array(
                'post_title' => $imovel['title'], // Título do imóvel
                'post_content' => $imovel['description'], // Descrição do imóvel
                'post_status' => 'publish',
                'post_type' => 'imovel',
            );

            // Insere o novo post
            $post_id = wp_insert_post($new_post);

                        
            // Extrai o  a ultima atualização
            $constructionStage = isset($imovel['construction_stage']) ? $imovel['construction_stage'] : null;

            // Extrai o  a ultima atualização
            $last_updated_at = isset($imovel['last_updated_at']) ? $imovel['last_updated_at'] : null;

            // Extrai o ID da unidade do apartamento
            $apartmentUnitId = isset($imovel['unit']['id']) ? $imovel['unit']['id'] : null;

            // Extrai o título do apartamento
            $apartmentTitle = isset($imovel['unit']['title']) ? $imovel['unit']['title'] : null;

            // Extrai o preço do apartamento
            $apartmentPrice = isset($imovel['unit']['price']) ? $imovel['unit']['price'] : null;

            // Extrai o tipo do apartamento
            $apartmentType = isset($imovel['unit']['type']) ? $imovel['unit']['type'] : null;

            // Extrai o número de vagas de garagem do apartamento
            $apartmentParkingSpaces = isset($imovel['unit']['parking_spaces']) ? $imovel['unit']['parking_spaces'] : null;

            // Extrai o número de quartos do apartamento
            $apartmentBedrooms = isset($imovel['unit']['dorms']) ? $imovel['unit']['dorms'] : null;

            // Extrai o número de suítes do apartamento
            $apartmentSuites = isset($imovel['unit']['suites']) ? $imovel['unit']['suites'] : null;

            // Extrai o número de banheiros do apartamento
            $apartmentBathrooms = isset($imovel['unit']['bathroom']) ? $imovel['unit']['bathroom'] : null;

            // Extrai a área privada do apartamento
            $apartmentPrivateArea = isset($imovel['unit']['private_area']) ? $imovel['unit']['private_area'] : null;

            // Extrai a área útil do apartamento
            $apartmentUtilArea = isset($imovel['unit']['util_area']) ? $imovel['unit']['util_area'] : null;

            // Extrai a área total do apartamento
            $apartmentTotalArea = isset($imovel['unit']['total_area']) ? $imovel['unit']['total_area'] : null;

            // Agora você tem um array de URLs das galerias adicionais
            $apartmentAdditionalGalleries = isset($imovel['unit']['additional_galleries']) ? $imovel['unit']['additional_galleries'] : null;
            $processedGalleryAdditional = [];
            
            if ($apartmentAdditionalGalleries) {
                foreach ($apartmentAdditionalGalleries as $gallery) {
                    if (isset($gallery['files']) && is_array($gallery['files'])) {
                        foreach ($gallery['files'] as $file) {
                            if (isset($file['url'])) {
                                $url = $file['url'];
                                $processedGalleryAdditional[] = $url;
                            }
                        }
                    }
                }
            }
            
            // Building Começa aqui 

            $buildingId = isset($imovel['building']['id']) ? $imovel['building']['id'] : null;
            $buildingTitle = isset($imovel['building']['title']) ? $imovel['building']['title'] : null;
            $buildingGallery = isset($imovel['building']['gallery']) ? $imovel['building']['gallery'] : null;
            $processedGallery = [];

            if ($buildingGallery) {
                foreach ($buildingGallery as $image) {
                    if (isset($image['url'])) {
                        $url = $image['url'];
                        $file_array = array(
                            'name' => basename($url),
                            'tmp_name' => download_url($url)
                        );
                        
                        if (!is_wp_error($file_array['tmp_name'])) {
                            $attachment_id = media_handle_sideload($file_array, 0);
                            
                            if (!is_wp_error($attachment_id)) {
                                $processedGallery[] = $attachment_id;
                            }
                        } else {
                            // Lida com o erro de download de imagem
                            $error_message = $file_array['tmp_name']->get_error_message();
                            error_log(json_encode($imovel["title"] . $error_message));
                        }
                    }
                }
            }
            
            if (!empty($processedGallery)) {
                update_field('field_building_gallery', $processedGallery, $post_id);
            }
                    
        
            
            
            
            $architecturalPlans = isset($imovel['building']['architectural_plans']) ? $imovel['building']['architectural_plans'] : null;
            
            if ($architecturalPlans) {
                $planUrls = [];
            
                foreach ($architecturalPlans as $plan) {
                    if (isset($plan['url'])) {
                        $planUrls[] = $plan['url'];
                    }
                }
            
                // Agora, a variável $planUrls conterá os URLs dos planos arquitetônicos do edifício.
            }
            
            $buildingVideo = isset($imovel['building']['video']) ? $imovel['building']['video'] : null;
            
            // O campo "video" conterá o URL do vídeo do edifício, caso exista.
            
            $buildingTour360 = isset($imovel['building']['tour_360']) ? $imovel['building']['tour_360'] : null;
            
            // O campo "tour_360" conterá o URL do tour em 360º do edifício, caso exista.
            
            $buildingDescription = isset($imovel['building']['description']) ? $imovel['building']['description'] : null;
            $descriptionTitle = null;
            $descriptionItems = null;
            
            if ($buildingDescription !== null && isset($buildingDescription[0]['title'])) {
                $descriptionTitle = $buildingDescription[0]['title'];
            
                if (isset($buildingDescription[0]['items']) && is_array($buildingDescription[0]['items'])) {
                    $descriptionItems = $buildingDescription[0]['items'];
                }
            }
            
            $buildingAddress = null;

        // Extrai endereço do building
            $address = isset($imovel['building']['address']) ? $imovel['building']['address'] : null;
            $streetName = isset($imovel['building']['address']['street_name']) ? $imovel['building']['address']['street_name'] : null;
            $streetNumber = isset($imovel['building']['address']['street_number']) ? $imovel['building']['address']['street_number'] : null;
            $neighborhood = isset($imovel['building']['address']['neighborhood']) ? $imovel['building']['address']['neighborhood'] : null;
            $complement = isset($imovel['building']['address']['complement']) ? $imovel['building']['address']['complement'] : null;
            $zipCode = isset($imovel['building']['address']['zip_code']) ? $imovel['building']['address']['zip_code'] : null;
            $city = isset($imovel['building']['address']['city']) ? $imovel['building']['address']['city'] : null;              
            $state = isset($imovel['building']['address']['state']) ? $imovel['building']['address']['state'] : null;
            $country = isset($imovel['building']['address']['country']) ? $imovel['building']['address']['country'] : null;
            $latitude = isset($imovel['building']['address']['latitude']) ? $imovel['building']['address']['latitude'] : null;
            $longitude = isset($imovel['building']['address']['longitude']) ? $imovel['building']['address']['longitude'] : null;
        
                        
            // O campo "address" conterá os detalhes do endereço do edifício.
            
            $buildingTextAddress = isset($imovel['building']['text_address']) ? $imovel['building']['text_address'] : null;
            
            // O campo "text_address" conterá o endereço formatado do edifício.
            
            $buildingIncorporation = isset($imovel['building']['incorporation']) ? $imovel['building']['incorporation'] : null;
            
            // O campo "incorporation" conterá informações sobre a incorporação do edifício.
            
            $buildingCover = isset($imovel['building']['cover']) ? $imovel['building']['cover'] : null;
            $coverUrl = null;
            
            if ($buildingCover && isset($buildingCover['url'])) {
                $coverUrl = $buildingCover['url'];
            }
            
            // Agora, a variável $coverUrl conterá a URL da imagem de capa do edifício, caso exista.
            


            $buildingFeatures = isset($imovel['building']['features']) ? $imovel['building']['features'] : null;

            if ($buildingFeatures) {
                $featureTags = [];
                $featureTypes = [];

                foreach ($buildingFeatures as $feature) {
                    if (isset($feature['tags']) && is_array($feature['tags'])) {
                        $featureTags = array_merge($featureTags, $feature['tags']);
                    }

                    if (isset($feature['type'])) {
                        $featureTypes[] = $feature['type'];
                    }
                }

                // Agora, a variável $featureTags conterá todas as tags das features do edifício.
                // E a variável $featureTypes conterá todos os tipos das features do edifício.
            }

            // O campo "delivery_date" conterá a data de entrega do edifício.
            
            $buildingDeliveryDate = isset($imovel['building']['delivery_date']) ? $imovel['building']['delivery_date'] : null;
        
                                                    
            // Define os metadados do imóvel
            update_post_meta($post_id, 'id', $imovel['id']);
            update_post_meta($post_id, 'title', $imovel['title']);
            update_post_meta($post_id, 'description', $imovel['description']);                
            update_field('construction_stage', $constructionStage, $post_id);
            update_field('last_updated_at', $last_updated_at, $post_id);

            // Metadados do apartamento
            update_field('apartment_unit_id', $apartmentUnitId, $post_id);
            update_field('apartment_title', $apartmentTitle, $post_id);
            update_field('apartment_price', $apartmentPrice, $post_id);
            update_field('apartment_type', $apartmentType, $post_id);
            update_field('apartment_parking_spaces', $apartmentParkingSpaces, $post_id);
            update_field('apartment_bedrooms', $apartmentBedrooms, $post_id);
            update_field('apartment_suites', $apartmentSuites, $post_id);
            update_field('apartment_bathrooms', $apartmentBathrooms, $post_id);
            update_field('apartment_private_area', $apartmentPrivateArea, $post_id);
            update_field('apartment_util_area', $apartmentUtilArea, $post_id);
            update_field('apartment_total_area', $apartmentTotalArea, $post_id);
            update_field('apartment_additional_galleries', $processedGalleryAdditional, $post_id);

            // Metadados do empreendimento
            update_field('building_id', $buildingId, $post_id);
            update_field('building_title', $buildingTitle, $post_id); 
            //update_field('building_gallery', $processedGallery, $post_id);
            update_field('building_text_address', $buildingTextAddress, $post_id);
     
            update_field('street_name', $streetName, $post_id);
            update_field('street_number', $streetNumber, $post_id);
            update_field('neighborhood', $neighborhood, $post_id);
            update_field('complement', $complement, $post_id);
            update_field('zip_code', $zipCode, $post_id);
            update_field('city', $city, $post_id);
            update_field('state', $state, $post_id);
            update_field('country', $country, $post_id);
            update_field('latitude', $latitude, $post_id);
            update_field('longitude', $longitude, $post_id);

            update_field('video_url', $buildingVideo, $post_id);
            update_field('tour360_url', $buildingTour360, $post_id); 
            update_field('building_description_title', $descriptionTitle, $post_id);
            update_field('building_description_items', $descriptionItems, $post_id);               
            update_field('building_cover_url', $coverUrl, $post_id);   
            update_field('building_features', $buildingFeatures, $post_id);                     
        
            update_field('building_delivery_date', $buildingDeliveryDate, $post_id);                    

            // Define o campo personalizado 

            //update_field('field_property_title', $imovel['title'], $post_id);
            //update_field('field_property_description', $imovel['description'], $post_id);
        
            update_field('field_construction_stage', $constructionStage, $post_id);
            update_field('last_updated_at', $last_updated_at, $post_id);

            update_field('field_apartment_unit_id', $apartmentUnitId, $post_id);
            update_field('field_apartment_title', $apartmentTitle, $post_id);
            update_field('field_apartment_price', $apartmentPrice, $post_id);
            update_field('field_apartment_type', $apartmentType, $post_id);
            update_field('field_apartment_parking_spaces', $apartmentParkingSpaces, $post_id);
            update_field('field_apartment_bedrooms', $apartmentBedrooms, $post_id);
            update_field('field_apartment_suites', $apartmentSuites, $post_id);
            update_field('field_apartment_bathrooms', $apartmentBathrooms, $post_id);
            update_field('field_apartment_private_area', $apartmentPrivateArea, $post_id);
            update_field('field_apartment_util_area', $apartmentUtilArea, $post_id);
            update_field('field_apartment_total_area', $apartmentTotalArea, $post_id);
            update_field('field_apartment_additional_galleries', $processedGalleryAdditional, $post_id);

            update_field('field_building_id', $buildingId, $post_id);
            update_field('field_building_title', $buildingTitle, $post_id);                          
            //update_field('field_building_gallery', $processedGallery, $post_id);

            update_field('field_video_url', $buildingVideo, $post_id);
            update_field('field_tour360_url', $buildingTour360, $post_id);
            update_field('field_building_description_title', $descriptionTitle, $post_id);
            update_field('field_building_description_items', $descriptionItems, $post_id);
            update_field('field_street_name', $streetName, $post_id);
            update_field('field_street_number', $streetNumber, $post_id);
            update_field('field_neighborhood', $neighborhood, $post_id);
            update_field('field_complement', $complement, $post_id);
            update_field('field_zip_code', $zipCode, $post_id);
            update_field('field_city', $city, $post_id);
            update_field('field_state', $state, $post_id);
            update_field('field_country', $country, $post_id);
            update_field('field_latitude', $latitude, $post_id);
            update_field('field_longitude', $longitude, $post_id);
            update_field('field_property_text_address', $buildingTextAddress, $post_id);
            update_field('field_building_cover_url', $coverUrl, $post_id);
                    
            update_field('field_delivery_date', $buildingDeliveryDate, $post_id);       
            // Atualiza o campo personalizado "field_property_value" com o ID da unidade         
        }
    }
    }
}

// Função para fazer upload de imagem e retornar o ID da imagem
function dwv_integration_upload_image($image_url, $post_id)
{
    // Faz o download da imagem
    $image_data = file_get_contents($image_url);

    // Gera um nome de arquivo único para evitar conflitos
    $file_name = 'dwv_integration_' . md5($image_url . time()) . '.jpg';

    // Define o caminho absoluto para o diretório de uploads
    $upload_dir = wp_upload_dir();

    // Cria o caminho absoluto para o arquivo
    $file_path = $upload_dir['path'] . '/' . $file_name;

    // Salva a imagem no diretório de uploads
    file_put_contents($file_path, $image_data);

    // Define os metadados do arquivo
    $attachment_data = array(
        'post_mime_type' => 'image/jpeg',
        'post_title' => sanitize_file_name($file_name),
        'post_content' => '',
        'post_status' => 'inherit'
    );

    // Insere o arquivo como um anexo
    $attach_id = wp_insert_attachment($attachment_data, $file_path, $post_id);

    // Gera os metadados do anexo
    $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);

    // Atualiza os metadados do anexo
    wp_update_attachment_metadata($attach_id, $attach_data);

    // Retorna o ID do anexo
    return $attach_id;
}

// Função para agendar a sincronização diária
function dwv_integration_schedule_sync()
{
    if (!wp_next_scheduled('dwv_integration_daily_sync')) {
        wp_schedule_event(time(), 'daily', 'dwv_integration_daily_sync');
    }
}
add_action('wp', 'dwv_integration_schedule_sync');

// Callback da sincronização diária
function dwv_integration_daily_sync()
{
    dwv_integration_sync_daily();
}
add_action('dwv_integration_daily_sync', 'dwv_integration_daily_sync');


function my_custom_admin_script() {
    wp_enqueue_script( 'admin-js', plugin_dir_url( __FILE__ ) . '/assets/js/admin.js', array( 'jquery' ), '1.0.0', true );
    wp_enqueue_style( 'admin-style' , plugin_dir_url( __FILE__ ) . '/assets/css/admin.css' );
    wp_localize_script( 'admin-js', 'wpurl',
    array( 
        'ajax' => admin_url( 'admin-ajax.php' ),
    ) );
}
 
add_action( 'admin_enqueue_scripts', 'my_custom_admin_script' );