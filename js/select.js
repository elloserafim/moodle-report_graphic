/**
 * Created by 08429611436 on 14/12/2017.
 */

window.onload = init;
var graphdata = null;
var urlCarregarCategoriasFilhas = 'classes/select.php';
var urlCarregarInfoCursos = 'classes/courseactivitychart.php';
var habilitarOnCHange = true;
function transpose(a) {
    return Object.keys(a[0]).map(function(c) {
        return a.map(function(r) { return r[c]; });
    });
}

//Gráfico Pizza com a porcentagem de cada usuário do curso
function gerarGraficoUsuariosAtivos(info, name){
    var data = new google.visualization.arrayToDataTable(
        info
    );

    var options = {'title':'Usuários mais ativos - '+ name ,
        'width':'100%',
        'height':500,
        animation: {startup: true, duration: 1000 },
        chartArea:{width: '90%'}
    };

    // Instantiate and draw the chart.
    var chart = new google.visualization.PieChart(document.getElementById('div_chart'));
    chart.draw(data, options);
}

//Gráfico Linha com as atividades de todos  os usuários do curso
function gerarGraficoAtividadeUsuario(data, name){

    for(var i = 1; i < data.length; i++){
        var arr = data[i][0].split('/');
        data[i][0] = new Date( Number(arr[1]), Number(arr[0])-1, 1);
    }

    var dataTableSales = google.visualization.arrayToDataTable(data);

    var viewColumns = [0];
    var aggColumns = (data);
    var view = new google.visualization.DataView(dataTableSales);
    view.setColumns(viewColumns);
    var datePicker = new google.visualization.ControlWrapper({
        controlType: 'ChartRangeFilter',
        containerId: 'div_chart3_filter',
        label: "Month",
        //'state':
        options: {
            filterColumnIndex: 0,
            ui: {
                'label': 'Filtrar datas',
                chartOptions: {
                    'legend': {'position': 'top'},
                    'chartArea': {'width': '50%', height:'50%'},
                    'hAxis': {'textPosition': 'out'},

                },
                labelStacking: 'vertical',
                snapToData: false
            },
            useFormattedValue: true
        }
    });

        var lineChart = new google.visualization.ChartWrapper({
            chartType: 'LineChart',
            containerId: 'div_chart3',
            options: {
                'title':'Eventos por mês - '+ name ,
                width: "100%",
                height: 300,
                'chartArea': {'width': '60%'},
            },

        });

        var dashboard = new google.visualization.Dashboard(document.getElementById('div_chart3_dashboard'));
        dashboard.bind(datePicker, lineChart);
        dashboard.draw(aggColumns);

}

//Gráfico barra com os evnetos mais comuns do curso
function gerarGraficoEventosComuns(info, name){
    var data = new google.visualization.arrayToDataTable(
        info
    );

    var options = {'title':'Eventos Comuns - '+ name ,
        'width':'100%',
        'height':500,
        animation: {startup: true, duration: 1000 },
        vAxis: {
            title: 'Quantidade'
        },
        chartArea:{width: '70%'}};

    // Instantiate and draw the chart.
    var chart = new google.visualization.ColumnChart(document.getElementById('div_chart2'));
    chart.draw(data, options);
}

function desabilitarCategoriasSeguintes(idElem){
    if(idElem == 5){
        return ;
    }
    var prox = $('#cat' + (++idElem));
    prox.html('<option>Escolher...</option>');
    prox.attr('disabled', 'disabled');
    return desabilitarCategoriasSeguintes(idElem);
}

function populateCourseOptionsFromGraph(data){
    var html = '';
    $('#btn_gerar_grafico').attr('disabled', 'disabled');

    if(data.length == 1) {
        html += '<option id="">Não há cursos com os filtros selecionados</option>';
        $('#courseid').attr('disabled', 'disabled');
    }
    else{
        $('#courseid').removeAttr('disabled');

        html += '<option id="">Escolher...</option>';
        for (var i = 1; i < data.length; i++) {
            html += '<option value="' + data[i][2] + '">' + data[i][0] + '</option>';
        }
    }

    $('#courseid').html(html);
    $('#spinner').hide();
    $('#courseselect').show();
}

function carregarCategoriasFilhasGrafico(element){
    var idElem = Number(element.attr('id').substr(3));
    var val = element.val();
    $('#spinner').show();
    $('#div_chart').html('');
    var carregouFilhas = false;
    var carregouCursos = false;

    //Carregar categorias filhas para popular os selects
    $.ajax({
        url: urlCarregarCategoriasFilhas,
        data: {categoryid: val},
        success: function (data, textStatus, jqXHR) {
            carregouFilhas = true;
            if (carregouCursos) {
                $('#spinner').hide();
            }


            if (idElem < 4) {
                var prox = $('#cat' + (idElem + 1));
                prox.html('<option>Escolher...</option>');
                prox.attr('disabled', 'disabled');

                var length = 0;
                var html = '';
                html += '<option>Escolher...</option>';
                for (var i in data) {
                    html += '<option value="' + data[i].id + '">' + data[i].name + '</option>';
                    length++;
                }
                if (length > 0) {
                    prox.removeAttr('disabled');
                    prox.html(html);
                }
            }
        },
        fail: function (jqXHR, textStatus, errorThrown) {

        }

    });

    var lastcategory = element.attr('id') == 'cat3';
    $.ajax({
        url: urlCarregarInfoCursos,
        data: {
            categoryid: val,
            activeonly: $('#activeonly').prop('checked'),
            forcechart: lastcategory,
        },
        success: function (data, textStatus, jqXHR) {

            $('.div_charts').html('');
            carregouCursos = true;
            if (carregouFilhas) {
                $('#spinner').hide();
            }
            if (typeof data != 'string') {
                containsGraph = true;
                gerarGraficoAtividadeCurso(data, element.find('option:selected').text());
                populateCourseOptionsFromGraph(data);
                desabilitarCategoriasSeguintes(idElem+1);
            } else {
                containsGraph = false;
                //$('#courseselect').hide();
                $('#div_chart').html(data);
            }
        },
        fail: function (jqXHR, textStatus, errorThrown) {
            console.log(jqXHR);
        },

    });

}

function gerarGraficoAtividadeCurso(info, catName) {
    if(info.length == 1){
        $('#div_chart').html('<h4>Não há dados para exibir com os filtros selecionados.</h4>');
        return;
    }

    // Define the chart to be drawn.
    var data = new google.visualization.DataTable();
    data.addColumn('string', info[0][0]);
    data.addColumn('number', info[0][1]);
    for(var i = 1; i < info.length; i++){
        data.addRow([info[i][0],info[i][1]] );
    }

    var options = {'title':'Atividade dos cursos - ' + catName,
        'width':'100%',
        'height':500,
        animation: {startup: true},
        chartArea:{width: '90%'}};

    // Instantiate and draw the chart.
    var chart = new google.visualization.PieChart(document.getElementById('div_chart'));
    chart.draw(data, options);
}

function init() {

    var containsGraph = false;
    var selectedCategoryElement = null;
    var btnGerarGrafico = $("#btn_gerar_grafico");
    var btnSelecionarCurso = $('#courseselect');
    google.charts.load('current', {packages: ['corechart', 'controls'], 'language': 'pt-BR'});

    //Ao alterar qualquer categoria
    $('.category').change(function(ev) {
        var elemento = $(this);
        var idElem = Number(elemento.attr('id').substr(3));
        if(elemento.find('option:selected')){
            var valor = elemento.find('option:selected').attr('value');
            selectedCategoryElement = elemento;
        }

        $('#courseselect').hide();

        if(valor != null && valor!= '') {
            carregarCategoriasFilhasGrafico(elemento);
        }
        else{
            var prox = $('#cat' + (idElem + 1));
            if(prox != null) {
                prox.html('<option>Escolher...</option>');
                prox.attr('disabled', 'disabled');
                prox.trigger('change');
            }
            var parent = $('#cat' + (idElem - 1));
            if(parent.length){
                //TODO Como evitar recursão infinita?
                //carregarCategoriasFilhasGrafico(parent);
            }
            else{
                $('#div_chart').html('<h3>Por favor, refine sua pesquisa.</h3>');
            }

        }
    });

    //Ao clicar no botão "gerar gráfico"
    $("#btn_gerar_grafico").on('click', function (){

        var selectedcourse = $('#courseid').find('option:selected');
        if(selectedcourse != null) {
            $.ajax({
                url: 'classes/reportcharts.php',
                data: {courseid: selectedcourse.attr('value')},
                success: function (data) {
                    graphdata = data;
                    $('.div_charts').html('');

                    gerarGraficoUsuariosAtivos(data[0], data[3]);
                    gerarGraficoEventosComuns(data[1], data[3]);
                    gerarGraficoAtividadeUsuario(data[2], data[3]);
                }

            });
        }
    });

    //Ao alterar "ativos"
    $('#activeonly').on('change', function(){
        var checked = $(this).attr('checked');
        if(containsGraph){
            carregarCategoriasFilhasGrafico(selectedCategoryElement);
        }
    });

    //Ao alterar curso
    $('#courseid').on('change', function () {
        var val = $(this).val();
        if(val != null && val != ''){
            $('#btn_gerar_grafico').removeAttr('disabled');
        }
    });

}