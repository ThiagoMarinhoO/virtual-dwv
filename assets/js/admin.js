jQuery(document).ready(function($) {
    var syncUpdateText = localStorage.getItem('lastUpdate');

    if (!syncUpdateText) {
        $(".lastUpdateInfos").css("display", "none");
    } else {
        $(".lastUpdateDate").text(syncUpdateText);
    }

    $('.progress-showing').hide();

    // Mostrar o spinner
    function showSpinner() {
        $('.progress-showing').show();
    }

    // Ocultar o spinner
    function hideSpinner() {
        $('.progress-showing').hide();
    }

    let imoveis = []; // Sua lista de imóveis
    let currentIndex = 0; // Índice do imóvel atual

    function sendImovel() {
        $("#syncImoveis").attr("disabled", "disabled");
        showSpinner();
        if (currentIndex < imoveis.length) {
            // Atualize o elemento HTML com a contagem da requisição atual
            $(".progress-label").text( "Sincronizado até agora " + (currentIndex + 1) + " de " + imoveis.length + "... Aguarde");

            $.ajax({
                url: wpurl.ajax,
                type: 'POST',
                data: {
                    action: 'dwv_integration_ajax_sync',
                    imovel: imoveis[currentIndex],
                },
                success: function (response) {
                    // Lógica de tratamento de sucesso
                    console.log(response.data.message);
                    currentIndex++; // Avança para o próximo imóvel
                    sendImovel(); // Chama a próxima iteração
                },
                error: function (error) {
                    // Lógica de tratamento de erro
                    console.log('Erro ao cadastrar imóvel:', error);
                    hideSpinner();
                    $("#syncImoveis").removeAttr("disabled");
                }
            });
        } else {
            // Todos os imóveis foram processados
            console.log('Todos os imóveis foram cadastrados.');

            hideSpinner();

            $("#syncImoveis").removeAttr("disabled");

            var currentDate = new Date();
            var day = currentDate.getDate();
            var month = currentDate.getMonth() + 1; // Months are 0-based
            var year = currentDate.getFullYear();
            var hours = currentDate.getHours();
            var minutes = currentDate.getMinutes();

            // Format the date and time as a string
            var dateText = day + "/" + month + "/" + year;
            var timeText = hours + ":" + minutes;

            $(".lastUpdateInfos").css("display", "flex");
            let lastUpdateDateText = `Última atualização: ${dateText} às ${timeText}`;
            $(".lastUpdateDate").text(lastUpdateDateText);
            localStorage.setItem("lastUpdate", lastUpdateDateText);
        }
    }

    function getImoveisFromPage(urlApi, authToken, page) {
        $.ajax({
            url: `${urlApi}/integration/properties?page=${page}`,
            type: 'GET',
            headers: {
                'Authorization': `Bearer ${authToken}`
            },
            dataType: 'json',
            success: function (response) {
                const pageImoveis = response.data;
                imoveis = imoveis.concat(pageImoveis);

                if (page < response.lastPage) {
                    // Continue obtendo dados das próximas páginas
                    getImoveisFromPage(urlApi, authToken, page + 1);
                } else {
                    // Inicialize a contagem e a barra de progresso
                    currentIndex = 0;

                    sendImovel(); // Inicia o processo de envio dos imóveis
                }
            },
            error: function (error) {
                // Trate erros aqui.
                console.log('Erro ao obter a lista de imóveis:', error);
            }
        });
    }

    $("#syncImoveis").on('click', function(e) {
        e.preventDefault();

        let authToken = $("#dwv_integration_token").val();
        let urlApi = $("#dwv_integration_url").val();

        // Inicie obtendo a primeira página de imóveis
        getImoveisFromPage(urlApi, authToken, 1);
    });
});
