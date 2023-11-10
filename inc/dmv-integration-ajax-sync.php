<?php

add_action('wp_ajax_dwv_integration_ajax_sync', 'dwv_integration_ajax_sync');
add_action('wp_ajax_nopriv_dwv_integration_ajax_sync', 'dwv_integration_ajax_sync');

function dwv_integration_ajax_sync() {
    if (isset($_POST['imovel'])) {
        $imovel = $_POST['imovel'];

        $status_imovel = $imovel['status'];
        $post_id = $imovel['id'];

        if ($status_imovel != "active") {
            // Movendo o post para a lixeira
            wp_trash_post($post_id);
        } else {
            // Verificando se o post está na lixeira
            $post_status = get_post_status($post_id);
            if ($post_status === 'trash') {
                // Restaurando o post
                $post_data = array(
                    'ID' => $post_id,
                    'post_status' => 'publish',
                );
                wp_update_post($post_data);
            }
        }

        $existing_post = get_page_by_title($imovel['title'], OBJECT, 'imovel');
        
        if ($existing_post) {
            //variável da última atualização do WP_post
            $post_last_update = '';

            $published_at = get_field('field_last_updated_at' , $existing_post);
            $post_modified_date = get_the_modified_date('', $existing_post);

            log_to_file('published_at: ' . $published_at);
            log_to_file('post_modified_date: ' . $post_modified_date);

            //Se não tiver sido modificado a variável terá o valor do published At
            if ($post_modified_date) {
                // Exibe a data de modificação se estiver disponível
                $post_last_update = $post_modified_date;
            } else {
                // Exibe a data de publicação original se não houver modificação
                $post_last_update = $published_at;
            }

            if(strtotime($imovel['last_updated_at']) >= strtotime($post_last_update) && $published_at != '') {
                // Exemplo de resposta bem-sucedida
                $response = array(
                    'message' => 'Imóvel já atualizado'
                );

                wp_send_json_success($response);
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

            // Agora você tem um array de URLs das galerias adicionais
            $apartmentAdditionalGalleries = isset($imovel['unit']['additional_galleries']) ? $imovel['unit']['additional_galleries'] : null;
            $unitGalleryAdditional = [];
            
            if ($apartmentAdditionalGalleries) {
                log_to_file('Cheguei nas plantas');
                $index = 1;
                
                foreach ($apartmentAdditionalGalleries as $image) {
                    if (isset($image['url'])) {
                        log_to_file('Baixando planta ' . $index);
                        $url = $image['url'];
                        
                        // Baixa a imagem
                        $tmp_name = download_url($url);
            
                        if (!is_wp_error($tmp_name)) {
                            log_to_file('Não deu erro');
                            // Redimensiona e comprime a imagem
                            $image_data = wp_get_image_editor($tmp_name);
                            if (!is_wp_error($image_data)) {
                                $image_data->resize(800, 600, true); // Redimensiona para as dimensões desejadas
                                $image_data->save($tmp_name);
            
                                // Adiciona a imagem otimizada ao WordPress
                                $file_array = array(
                                    'name' => basename($url),
                                    'tmp_name' => $tmp_name
                                );
                                
                                $attachment_id = media_handle_sideload($file_array, 0);
                                
                                if (!is_wp_error($attachment_id)) {
                                    $unitGalleryAdditional[] = $attachment_id;
                                    log_to_file('Baixou a planta :)');
                                } else {
                                    log_to_file('Deu erro :(');
                                    $error_message = $attachment_id->get_error_message();
                                    log_to_file(json_encode($imovel["title"] . $error_message));
                                }
                            } else {
                                log_to_file('Erro ao redimensionar imagem: ' . $image_data->get_error_message());
                            }
                        } else {
                            log_to_file('Erro ao fazer download da imagem: ' . $tmp_name->get_error_message());
                        }
                    }
                    $index++;
                }
            }

            if (!empty($unitGalleryAdditional)) {
                update_field('field_apartment_additional_galleries', $unitGalleryAdditional, $post_id);
                log_to_file("supostamente adicionado imagens ao campo acf");
            }
            
            // Building Começa aqui 

            $buildingId = isset($imovel['building']['id']) ? $imovel['building']['id'] : null;
            $buildingTitle = isset($imovel['building']['title']) ? $imovel['building']['title'] : null;
            $buildingGallery = isset($imovel['building']['gallery']) ? $imovel['building']['gallery'] : null;
            $buildingArchitecturalPlans = isset($imovel['building']['architectural_plans']) ? $imovel['building']['architectural_plans'] : null;
            $processedGallery = [];
            $processedArchitecturalPlans = [];

            if ($buildingGallery) {
                $i = 1;
                log_to_file('Chegamos na galeria');
                foreach ($buildingGallery as $image) {
                    log_to_file('Baixando imagem ' . $i);
                    if (isset($image['url'])) {
                        $url = $image['url'];
                        $tmp_name = download_url($url);
            
                        if (!is_wp_error($tmp_name)) {
                            log_to_file('Não deu erro');
            
                            // Redimensiona e comprime a imagem
                            $image_data = wp_get_image_editor($tmp_name);
                            if (!is_wp_error($image_data)) {
                                $image_data->resize(800, 600, true); // Redimensiona para as dimensões desejadas
                                $image_data->save($tmp_name);
            
                                // Adiciona a imagem otimizada ao WordPress
                                $file_array = array(
                                    'name'     => basename($url),
                                    'tmp_name' => $tmp_name
                                );
            
                                $attachment_id = media_handle_sideload($file_array, 0);
            
                                if (!is_wp_error($attachment_id)) {
                                    $processedGallery[] = $attachment_id;
                                    log_to_file('Deu bom :)');
                                } else {
                                    log_to_file('Deu ruim :(');
                                    // Lida com o erro de adição de imagem ao WordPress
                                    $error_message = $attachment_id->get_error_message();
                                    log_to_file(json_encode($imovel["title"] . $error_message));
                                }
                            } else {
                                log_to_file('Erro ao redimensionar imagem: ' . $image_data->get_error_message());
                            }
                        } else {
                            log_to_file('Erro ao fazer download da imagem: ' . $tmp_name->get_error_message());
                        }
                    }
                    $i++;
                }
            }

            if ($buildingArchitecturalPlans) {
                log_to_file('Cheguei nas plantas');
                $index = 1;
                
                foreach ($buildingArchitecturalPlans as $image) {
                    if (isset($image['url'])) {
                        log_to_file('Baixando planta ' . $index);
                        $url = $image['url'];
                        
                        // Baixa a imagem
                        $tmp_name = download_url($url);
            
                        if (!is_wp_error($tmp_name)) {
                            log_to_file('Não deu erro');
                            // Redimensiona e comprime a imagem
                            $image_data = wp_get_image_editor($tmp_name);
                            if (!is_wp_error($image_data)) {
                                $image_data->resize(800, 600, true); // Redimensiona para as dimensões desejadas
                                $image_data->save($tmp_name);
            
                                // Adiciona a imagem otimizada ao WordPress
                                $file_array = array(
                                    'name' => basename($url),
                                    'tmp_name' => $tmp_name
                                );
                                
                                $attachment_id = media_handle_sideload($file_array, 0);
                                
                                if (!is_wp_error($attachment_id)) {
                                    $processedArchitecturalPlans[] = $attachment_id;
                                    log_to_file('Baixou a planta :)');
                                } else {
                                    log_to_file('Deu erro :(');
                                    $error_message = $attachment_id->get_error_message();
                                    log_to_file(json_encode($imovel["title"] . $error_message));
                                }
                            } else {
                                log_to_file('Erro ao redimensionar imagem: ' . $image_data->get_error_message());
                            }
                        } else {
                            log_to_file('Erro ao fazer download da imagem: ' . $tmp_name->get_error_message());
                        }
                    }
                    $index++;
                }
            }
            
            if (!empty($processedGallery)) {
                update_field('field_building_gallery', $processedGallery, $post_id);
                log_to_file("supostamente adicionado imagens ao campo acf");
            }

            if(!empty($processedArchitecturalPlans)){
                update_field('field_apartment_floor_plans' , $processedArchitecturalPlans , $post_id);
                log_to_file("supostamente adicionado imagens ao campo acf");
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
            update_field('construction_stage', $constructionStage, $existing_post->ID);
            update_field('last_updated_at', $last_updated_at, $existing_post->ID);

            // Metadados do apartamento
            update_field('apartment_unit_id', $apartmentUnitId, $existing_post->ID);
            update_field('apartment_title', $apartmentTitle, $existing_post->ID);
            update_field('apartment_price', $apartmentPrice, $existing_post->ID);
            update_field('apartment_type', $apartmentType, $existing_post->ID);
            update_field('apartment_parking_spaces', $apartmentParkingSpaces, $existing_post->ID);
            update_field('apartment_bedrooms', $apartmentBedrooms, $existing_post->ID);
            update_field('apartment_suites', $apartmentSuites, $existing_post->ID);
            update_field('apartment_bathrooms', $apartmentBathrooms, $existing_post->ID);
            update_field('apartment_private_area', $apartmentPrivateArea, $existing_post->ID);
            update_field('apartment_util_area', $apartmentUtilArea, $existing_post->ID);
            update_field('apartment_total_area', $apartmentTotalArea, $existing_post->ID);
            // update_field('apartment_additional_galleries', $processedGalleryAdditional, $existing_post->ID);

            // Metadados do empreendimento
            update_field('building_id', $buildingId, $existing_post->ID);
            update_field('building_title', $buildingTitle, $existing_post->ID);
            // update_field('building_gallery', $processedGallery, $existing_post->ID);
            update_field('building_architectural_plans', $planUrls, $existing_post->ID);
            update_field('building_video', $buildingVideo, $existing_post->ID);
            update_field('building_tour_360', $buildingTour360, $existing_post->ID);
            update_field('building_description_title', $descriptionTitle, $existing_post->ID);
            update_field('building_description_items', $descriptionItems, $existing_post->ID);
            update_field('building_address_street_name', $streetName, $existing_post->ID);
            update_field('building_address_street_number', $streetNumber, $existing_post->ID);
            update_field('building_address_neighborhood', $neighborhood, $existing_post->ID);
            update_field('building_address_complement', $complement, $existing_post->ID);
            update_field('building_address_zip_code', $zipCode, $existing_post->ID);
            update_field('building_address_city', $city, $existing_post->ID);
            update_field('building_address_state', $state, $existing_post->ID);
            update_field('building_address_country', $country, $existing_post->ID);
            update_field('building_address_latitude', $latitude, $existing_post->ID);
            update_field('building_address_longitude', $longitude, $existing_post->ID);
            update_field('building_text_address', $buildingTextAddress, $existing_post->ID);

            // Exemplo de resposta bem-sucedida
            $response = array(
                'message' => 'Imóvel atualizado com sucesso'
            );

            wp_send_json_success($response);
        }else{
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
            $unitGalleryAdditional = [];

            if ($apartmentAdditionalGalleries) {
                log_to_file('Cheguei nas plantas');
                $index = 1;
                
                foreach ($apartmentAdditionalGalleries as $image) {
                    if (isset($image['url'])) {
                        log_to_file('Baixando planta ' . $index);
                        $url = $image['url'];
                        
                        // Baixa a imagem
                        $tmp_name = download_url($url);
            
                        if (!is_wp_error($tmp_name)) {
                            log_to_file('Não deu erro');
                            // Redimensiona e comprime a imagem
                            $image_data = wp_get_image_editor($tmp_name);
                            if (!is_wp_error($image_data)) {
                                $image_data->resize(800, 600, true); // Redimensiona para as dimensões desejadas
                                $image_data->save($tmp_name);
            
                                // Adiciona a imagem otimizada ao WordPress
                                $file_array = array(
                                    'name' => basename($url),
                                    'tmp_name' => $tmp_name
                                );
                                
                                $attachment_id = media_handle_sideload($file_array, 0);
                                
                                if (!is_wp_error($attachment_id)) {
                                    $unitGalleryAdditional[] = $attachment_id;
                                    log_to_file('Baixou a planta :)');
                                } else {
                                    log_to_file('Deu erro :(');
                                    $error_message = $attachment_id->get_error_message();
                                    log_to_file(json_encode($imovel["title"] . $error_message));
                                }
                            } else {
                                log_to_file('Erro ao redimensionar imagem: ' . $image_data->get_error_message());
                            }
                        } else {
                            log_to_file('Erro ao fazer download da imagem: ' . $tmp_name->get_error_message());
                        }
                    }
                    $index++;
                }
            }

            if (!empty($unitGalleryAdditional)) {
                update_field('field_apartment_additional_galleries', $unitGalleryAdditional, $post_id);
                log_to_file("supostamente adicionado imagens ao campo acf");
            }
            
            // Building Começa aqui 

            $buildingId = isset($imovel['building']['id']) ? $imovel['building']['id'] : null;
            $buildingTitle = isset($imovel['building']['title']) ? $imovel['building']['title'] : null;
            $buildingGallery = isset($imovel['building']['gallery']) ? $imovel['building']['gallery'] : null;
            $buildingArchitecturalPlans = isset($imovel['building']['architectural_plans']) ? $imovel['building']['architectural_plans'] : null;
            $processedGallery = [];
            $processedArchitecturalPlans = [];

            log_to_file('Até aqui cheguei');

            if ($buildingGallery) {
                $i = 1;
                log_to_file('Chegamos na galeria');
                foreach ($buildingGallery as $image) {
                    log_to_file('Baixando imagem ' . $i);
                    if (isset($image['url'])) {
                        $url = $image['url'];
                        $tmp_name = download_url($url);
            
                        if (!is_wp_error($tmp_name)) {
                            log_to_file('Não deu erro');
            
                            // Redimensiona e comprime a imagem
                            $image_data = wp_get_image_editor($tmp_name);
                            if (!is_wp_error($image_data)) {
                                $image_data->resize(800, 600, true); // Redimensiona para as dimensões desejadas
                                $image_data->save($tmp_name);
            
                                // Adiciona a imagem otimizada ao WordPress
                                $file_array = array(
                                    'name'     => basename($url),
                                    'tmp_name' => $tmp_name
                                );
            
                                $attachment_id = media_handle_sideload($file_array, 0);
            
                                if (!is_wp_error($attachment_id)) {
                                    $processedGallery[] = $attachment_id;
                                    log_to_file('Deu bom :)');
                                } else {
                                    log_to_file('Deu ruim :(');
                                    // Lida com o erro de adição de imagem ao WordPress
                                    $error_message = $attachment_id->get_error_message();
                                    log_to_file(json_encode($imovel["title"] . $error_message));
                                }
                            } else {
                                log_to_file('Erro ao redimensionar imagem: ' . $image_data->get_error_message());
                            }
                        } else {
                            log_to_file('Erro ao fazer download da imagem: ' . $tmp_name->get_error_message());
                        }
                    }
                    $i++;
                }
            }
            

            if ($buildingArchitecturalPlans) {
                log_to_file('Cheguei nas plantas');
                $index = 1;
                
                foreach ($buildingArchitecturalPlans as $image) {
                    if (isset($image['url'])) {
                        log_to_file('Baixando planta ' . $index);
                        $url = $image['url'];
                        
                        // Baixa a imagem
                        $tmp_name = download_url($url);
            
                        if (!is_wp_error($tmp_name)) {
                            log_to_file('Não deu erro');
                            // Redimensiona e comprime a imagem
                            $image_data = wp_get_image_editor($tmp_name);
                            if (!is_wp_error($image_data)) {
                                $image_data->resize(800, 600, true); // Redimensiona para as dimensões desejadas
                                $image_data->save($tmp_name);
            
                                // Adiciona a imagem otimizada ao WordPress
                                $file_array = array(
                                    'name' => basename($url),
                                    'tmp_name' => $tmp_name
                                );
                                
                                $attachment_id = media_handle_sideload($file_array, 0);
                                
                                if (!is_wp_error($attachment_id)) {
                                    $processedArchitecturalPlans[] = $attachment_id;
                                    log_to_file('Baixou a planta :)');
                                } else {
                                    log_to_file('Deu erro :(');
                                    $error_message = $attachment_id->get_error_message();
                                    log_to_file(json_encode($imovel["title"] . $error_message));
                                }
                            } else {
                                log_to_file('Erro ao redimensionar imagem: ' . $image_data->get_error_message());
                            }
                        } else {
                            log_to_file('Erro ao fazer download da imagem: ' . $tmp_name->get_error_message());
                        }
                    }
                    $index++;
                }
            }
            
            
            if (!empty($processedGallery)) {
                update_field('field_building_gallery', $processedGallery, $post_id);
                log_to_file("supostamente adicionado imagens ao campo acf");
            }

            if(!empty($processedArchitecturalPlans)){
                log_to_file('Entrei pra cadastrar a galeria');
                update_field('field_apartment_floor_plans' , $processedArchitecturalPlans , $post_id);
                log_to_file("supostamente adicionado plantas ao campo acf");
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
            update_post_meta($post_id, 'construction_stage', $constructionStage);
            update_post_meta($post_id, 'last_updated_at', $last_updated_at);

            // Metadados do apartamento
            update_post_meta($post_id, 'apartment_unit_id', $apartmentUnitId);
            update_post_meta($post_id, 'apartment_title', $apartmentTitle);
            update_post_meta($post_id, 'apartment_price', $apartmentPrice);
            update_post_meta($post_id, 'apartment_type', $apartmentType);
            update_post_meta($post_id, 'apartment_parking_spaces', $apartmentParkingSpaces);
            update_post_meta($post_id, 'apartment_bedrooms', $apartmentBedrooms);
            update_post_meta($post_id, 'apartment_suites', $apartmentSuites);
            update_post_meta($post_id, 'apartment_bathrooms', $apartmentBathrooms);
            update_post_meta($post_id, 'apartment_private_area', $apartmentPrivateArea);
            update_post_meta($post_id, 'apartment_util_area', $apartmentUtilArea);
            update_post_meta($post_id, 'apartment_total_area', $apartmentTotalArea);
            // update_post_meta($post_id, 'apartment_additional_galleries', $processedGalleryAdditional);

            // Metadados do empreendimento
            update_post_meta($post_id, 'building_id', $buildingId);
            update_post_meta($post_id, 'building_title', $buildingTitle); 
            update_post_meta($post_id, 'building_gallery', $processedGallery);
            update_post_meta($post_id, 'building_text_address', $buildingTextAddress);
            

            
            update_post_meta($post_id, 'street_name', $streetName);
            update_post_meta($post_id, 'street_number', $streetNumber);
            update_post_meta($post_id, 'neighborhood', $neighborhood);
            update_post_meta($post_id, 'complement', $complement);
            update_post_meta($post_id, 'zip_code', $zipCode);
            update_post_meta($post_id, 'city', $city);
            update_post_meta($post_id, 'state', $state);
            update_post_meta($post_id, 'country', $country);
            update_post_meta($post_id, 'latitude', $latitude);
            update_post_meta($post_id, 'longitude', $longitude);
            
        

            update_post_meta($post_id, 'video_url', $buildingVideo);
            update_post_meta($post_id, 'tour360_url', $buildingTour360); 
            update_post_meta($post_id, 'building_description_title', $descriptionTitle);
            update_post_meta($post_id, 'building_description_items', $descriptionItems);               
            update_post_meta($post_id, 'building_cover_url', $coverUrl);   
            update_post_meta($post_id, 'building_features', $buildingFeatures);               
            

            
        
            update_post_meta($post_id, 'building_delivery_date', $buildingDeliveryDate);

                        

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
            // update_field('field_apartment_additional_galleries', $processedGalleryAdditional, $post_id);

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

            // Exemplo de resposta bem-sucedida
            $response = array(
                'message' => 'Imóvel cadastrado com sucesso'
            );

            wp_send_json_success($response);
        }
    }
}
