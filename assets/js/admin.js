jQuery(document).ready(function($) {
    var syncUpdateText = localStorage.getItem('lastUpdate');

    if (!syncUpdateText) {
        $(".lastUpdateInfos").css("display", "none");
    } else {
        $(".lastUpdateDate").text(syncUpdateText);
    }

    $('.progress-showing').hide();

    let imoveis = [];
    let currentIndex = 0;
    const maxRequestsPerSecond = 5; // Limite de solicitações por segundo
    let failedImoveis = []; // Lista para manter os imóveis que falharam

    function showSpinner() {
        $('.progress-showing').show();
    }

    function hideSpinner() {
        $('.progress-showing').hide();
    }

    function processImovel(imovel) {
        $.ajax({
            url: wpurl.ajax,
            type: 'POST',
            timeout: 1800000,
            data: {
                action: 'dwv_integration_ajax_sync',
                imovel: imovel,
            },
            success: function (response) {
                console.log(response.data)
                currentIndex++;
                sendImovel();
            },
            error: function (error) {
                console.log('Erro ao cadastrar imóvel:', error);
                hideSpinner();
                $("#syncImoveis").removeAttr("disabled");
                failedImoveis.push(imovel); // Adicione o imóvel à lista de falhas
                currentIndex++; // Avance para o próximo imóvel
                sendImovel(); // Continue com o próximo imóvel
            }
        });
    }

    function sendImovel() {
        $("#syncImoveis").attr("disabled", "disabled");
        showSpinner();
        if (currentIndex < imoveis.length) {
            // Se houver imóveis na lista de falhas, tente com eles primeiro
            if (failedImoveis.length > 0) {
                imoveis = failedImoveis;
                failedImoveis = []; // Limpe a lista de falhas
            }
            
            $(".progress-label").text( "Sincronizado até agora " + (currentIndex + 1) + " de " + imoveis.length + "... Aguarde");

            processImovel(imoveis[currentIndex]);
        } else {
            console.log('Todos os imóveis foram cadastrados.');
            hideSpinner();
            $("#syncImoveis").removeAttr("disabled");

            var currentDate = new Date();
            var day = currentDate.getDate();
            var month = currentDate.getMonth() + 1;
            var year = currentDate.getFullYear();
            var hours = currentDate.getHours();
            var minutes = currentDate.getMinutes();

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
            timeout: 1800000,
            headers: {
                'Authorization': `Bearer ${authToken}`,
                'accept-encoding': 'gzip, deflate',
            },
            dataType: 'json',
            success: function (response) {
                const pageImoveis = response.data;
                imoveis = imoveis.concat(pageImoveis);
    
                if (page < response.lastPage) {
                    setTimeout(function() {
                        getImoveisFromPage(urlApi, authToken, page + 1);
                    }, 1000 / maxRequestsPerSecond);
                } else {
                    currentIndex = 0;
                    sendImovel();
                }
            },
            error: function (xhr, status, error) {
                console.log('Erro ao obter a lista de imóveis:', error);
    
                // Tentar novamente após um atraso
                setTimeout(function() {
                    getImoveisFromPage(urlApi, authToken, page);
                    console.log('Tentando novamente')
                }, 5000); // Tente novamente após 5 segundos
            }
        });
    }

    function processImoveisLocally(imoveis) {
        currentIndex = 0;
        sendImovel();
    }

    $("#syncImoveis").on('click', function(e) {
        e.preventDefault();

        let authToken = $("#dwv_integration_token").val();
        let urlApi = $("#dwv_integration_url").val();

        getImoveisFromPage(urlApi, authToken, 1);
    });

    // Processar imóveis localmente após carregamento
    if (localStorage.getItem('storedImoveis')) {
        imoveis = JSON.parse(localStorage.getItem('storedImoveis'));
        processImoveisLocally(imoveis);
    }
});
