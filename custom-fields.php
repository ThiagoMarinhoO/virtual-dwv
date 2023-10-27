<?php
// custom-fields.php

function dwv_integration_custom_fields() {
  
  // Verifica se o plugin Advanced Custom Fields está ativo
  if (function_exists('acf_add_local_field_group')) {
    
    // Informações do Apartamento Unidade
    acf_add_local_field_group(array(
      'key' => 'group_apartamento_info',
      'title' => 'Unidade (Apartamento)',
      'fields' => array(
        array(
          'key' => 'field_apartment_title',
          'label' => 'Título do Apartamento',
          'name' => 'apartment_title',
          'type' => 'text',
        ),
        array(
          'key' => 'field_apartment_unit_id',
          'label' => 'ID da Unidade do Apartamento',
          'name' => 'apartment_unit_id',
          'type' => 'text',
        ),
        array(
          'key' => 'field_apartment_description',
          'label' => 'Descrição do Apartamento',
          'name' => 'apartment_description',
          'type' => 'text',
        ),
        
        
        array(
          'key' => 'field_apartment_price',
          'label' => 'Preço do Apartamento',
          'name' => 'apartment_price',
          'type' => 'number',
        ),
        array(
          'key' => 'field_apartment_additional_galleries',
          'label' => 'Galeria de Imagens da Unidade',
          'name' => 'apartment_gallery',
          'type' => 'gallery', // Tipo de campo galeria
          'instructions' => 'Selecione ou faça upload de imagens para a galeria do apartamento.',
          
          // 'mime_types' => 'jpg,jpeg,png', // Tipos de arquivo permitidos
        ),


        array(
          'key' => 'field_apartment_type',
          'label' => 'Tipo do Apartamento',
          'name' => 'apartment_type',
          'type' => 'text',
        ),
        array(
          'key' => 'field_apartment_parking_spaces',
          'label' => 'Vagas de Garagem do Apartamento',
          'name' => 'apartment_parking_spaces',
          'type' => 'number',
        ),
        array(
          'key' => 'field_apartment_bedrooms',
          'label' => 'Número de Quartos do Apartamento',
          'name' => 'apartment_bedrooms',
          'type' => 'number',
        ),
        array(
          'key' => 'field_apartment_bathrooms',
          'label' => 'Número de Banheiros do Apartamento',
          'name' => 'apartment_bathrooms',
          'type' => 'number',
        ),
        array(
          'key' => 'field_apartment_suites',
          'label' => 'Suítes',
          'name' => 'unit_suites',
          'type' => 'text',
        ),
      
        array(
          'key' => 'field_apartment_private_area',
          'label' => 'Área Privada do Apartamento',
          'name' => 'apartment_private_area',
          'type' => 'text',
        ),
        array(
          'key' => 'field_apartment_util_area',
          'label' => 'Área Útil do Apartamento',
          'name' => 'apartment_util_area',
          'type' => 'text',
        ),
        array(
          'key' => 'field_apartment_total_area',
          'label' => 'Área Útil do Apartamento',
          'name' => 'apartment_total_area',
          'type' => 'text',
        ),     
       

        array(
          'key' => 'field_apartment_floor_plans',
          'label' => 'Plantas do Apartamento',
          'name' => 'apartment_floor_plans',
          'type' => 'text',
        ),
      ),
      'location' => array(
        array(
          array(
            'param' => 'post_type',
            'operator' => '==',
            'value' => 'imovel',
          ),
        ),
      ),
    ));





    // Informações do Prédio

    acf_add_local_field_group(array(
      'key' => 'group_address',
      'title' => 'Prédio',
      'fields' => array(
        array(
          'key' => 'field_building_title',
          'label' => 'Título do Edifício',
          'name' => 'building_title',
          'type' => 'text',
        ),
        array(
          'key' => 'field_building_id',
          'label' => 'ID do Edifício',
          'name' => 'building_id',
          'type' => 'text',
        ),
        array(
          'key' => 'field_property_text_address',
          'label' => 'Endereço',
          'name' => 'address',
          'type' => 'text',
        ),
        array(
          'key' => 'field_state',
          'label' => 'Estado',
          'name' => 'state',
          'type' => 'text',
        ),
        
        
        array(
          'key' => 'field_construction_stage',
          'label' => 'Estágio de Construção',
          'name' => 'construction_stage',
          'type' => 'text',
        ),
        array(
          'key' => 'field_building_cover_url',
          'label' => 'URL da Imagem de Capa do Edifício',
          'name' => 'building_cover_url',
          'type' => 'text',
        ),
        
        array(
          'key' => 'field_building_gallery',
          'label' => 'Galeria de Imagens do Prédio',
          'name' => 'building_gallery',
          'type' => 'gallery',  // Tipo de campo galeria
          'instructions' => 'Selecione ou faça upload de imagens para a galeria do apartamento.',
          
           // Tipos de arquivo permitidos
        ),
        array(
          'key' => 'field_street_name',
          'label' => 'Nome da Rua',
          'name' => 'street_name',
          'type' => 'text',
        ),
        array(
          'key' => 'field_street_number',
          'label' => 'Número da Rua',
          'name' => 'street_number',
          'type' => 'text',
        ),
        array(
          'key' => 'field_neighborhood',
          'label' => 'Bairro',
          'name' => 'neighborhood',
          'type' => 'text',
        ),
        array(
          'key' => 'field_complement',
          'label' => 'Complemento',
          'name' => 'complement',
          'type' => 'text',
        ),
        array(
          'key' => 'field_zip_code',
          'label' => 'CEP',
          'name' => 'zip_code',
          'type' => 'text',
        ),
        array(
          'key' => 'field_city',
          'label' => 'Cidade',
          'name' => 'city',
          'type' => 'text',
        ),
        array(
          'key' => 'field_state',
          'label' => 'Estado',
          'name' => 'state',
          'type' => 'text',
        ),
        array(
          'key' => 'field_country',
          'label' => 'País',
          'name' => 'country',
          'type' => 'text',
        ),
        array(
          'key' => 'field_latitude',
          'label' => 'Latitude',
          'name' => 'latitude',
          'type' => 'text',
        ),
        array(
          'key' => 'field_longitude',
          'label' => 'Longitude',
          'name' => 'longitude',
          'type' => 'text',
        ),
        array(
          'key' => 'field_video_url',
          'label' => 'URL do Vídeo',
          'name' => 'video_url',
          'type' => 'text',
        ),
        array(
          'key' => 'field_tour360_url',
          'label' => 'URL do Tour 360',
          'name' => 'tour360_url',
          'type' => 'text',
        ),
        array(
          'key' => 'field_last_updated_at',
          'label' => 'última Atualização',
          'name' => 'last_updated_at',
          'type' => 'text',
        ),


      ),
      'location' => array(
        array(
          array(
            'param' => 'post_type',
            'operator' => '==',
            'value' => 'imovel',
          ),
        ),
      ),
    ));   
  }
}
add_action('acf/init', 'dwv_integration_custom_fields');
?>
