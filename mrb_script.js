jQuery(document).ready(function () {

    var champs = ['marques', 'modeles', 'motorisations', 'annees']; //Tous les selects
    var SelectedValues = {}; // Les choix successifs de l'internaute

    /*
     * Envoie la requete ajax au PHP
     * @param {string} dataRequest Utilisé coté php pour executer la bonne requete
     * @param {Object} selectedValues Choix successifs de l'internaute
     * @returns {undefined}
     */
    function sendRequest(dataRequest, selectedValues) {
        jQuery.post(
                ajax_url,
                {
                    action: 'get_batteries',
                    data: {
                        requete: 'get_' + dataRequest,
                        selectedValues: JSON.stringify(selectedValues)
                    }
                    
                },
                function (response) {
                    createOptions(response, '#mrb_' + dataRequest);
                }
        );
    }

    /*
     * Crée les options pour un select
     * @param {string} response La reponse au format JSON
     * @param {string} select Le select à remplir
     * @returns {undefined}
     */
    function createOptions(response, select) {
        var selectInput = jQuery(select);
        var datas = jQuery.parseJSON(response);
   
        jQuery.each(datas, function (key, value) {
            selectInput.append(jQuery("<option/>", {
                value: value,
                text: value
            }));
        });
    }

    /*
     * Génère les options pour tous les champs
     */
    jQuery.each(champs, function (index, value) {
        
        var currentInput = value,
            nextInput = champs[index + 1];
            
        /*Le premier*/
        if (index == 0) {
            sendRequest(currentInput, SelectedValues);
        }
        
        /*Les autres lors de l'event Onchange*/
        jQuery('#mrb_' + currentInput).on('change', function () { 
            var currentSelect = jQuery(this),
                currentChoice = currentSelect.find("option:selected").text(); 

            SelectedValues[value] = currentChoice; //On ajoute le choix courrant à l'objet SelectedValue
            sendRequest(nextInput, SelectedValues);
            
            /* A factoriser, améliorer - Permet de gérer les disabled + reset*/
            jQuery('#mrb_' + nextInput).find('option').remove();
            jQuery('#mrb_' + nextInput).html('<option value="">Choisissez</option>');
            jQuery('#mrb_' + nextInput).attr('disabled', false);
            
            var IndexOfCurrentChoice = champs.indexOf(currentSelect.attr('name')); 
            for(var i = IndexOfCurrentChoice +1; i<champs.length; i++){
                jQuery('#mrb_' + champs[i]).find('option').remove();
                jQuery('#mrb_' + champs[i]).html('<option value="">Choisissez</option>');
                jQuery('#mrb_' + champs[i +1]).attr('disabled', true);   
            }
        })
    });
    
});