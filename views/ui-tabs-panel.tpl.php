<?php defined('ABSPATH') or exit; ?>
<input type='hidden' name='cf7mediapost_form_fields' value='{mp_fields_hidden}'>
<div class="cf7mediapost">
    <fieldset>
        {erro}
        <div class="accordion {api_active}"><h2><span class="rounded {api_status}">1</span>Configurações de Integração com @MediaPost</h2></div>
        <div class="panel">
            <p>Insira as credenciais abaixo e salve o formulário para mais opções.</p>
            <p><b>Não possui os dados? Envie um e-mail para <a href="mailto:suporte@mediapost.com.br">suporte@mediapost.com.br</a> para mais informações.</b></p>
            {mp_conta}
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><label for="cf7mediapost_consumer_key">Consumer Key</label></th>
                        <td>
                            <input type="text" name="cf7mediapost_consumer_key" id="cf7mediapost_consumer_key" class="large-text code" value="{consumer_key}">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cf7mediapost_consumer_secret">Consumer Secret</label></th>
                        <td>
                            <input type="text" name="cf7mediapost_consumer_secret" id="cf7mediapost_consumer_secret" class="large-text code" value="{consumer_secret}">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cf7mediapost_token">Token</label></th>
                        <td>
                            <input type="text" name="cf7mediapost_token" id="cf7mediapost_token" class="large-text code" value="{token}">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cf7mediapost_token_secret">Token Secret</label></th>
                        <td>
                            <input type="text" name="cf7mediapost_token_secret" id="cf7mediapost_token_secret" class="large-text code" value="{token_secret}">
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {html_status}
        {html_listas}
        {html_campos}
    </fieldset>
</div>
<script>
var acc = document.getElementsByClassName("accordion");
var i;

for (i = 0; i < acc.length; i++) {
  acc[i].addEventListener("click", function() {
    this.classList.toggle("active");
    var panel = this.nextElementSibling;
    if (panel.style.maxHeight){
      panel.style.maxHeight = null;
    } else {
      panel.style.maxHeight = panel.scrollHeight + "px";
    }
  });
}

var elem_act = document.getElementsByClassName("load_active");
document.addEventListener("DOMContentLoaded", function() {
    elem_act[0].classList.toggle("active");
    var panel = elem_act[0].nextElementSibling;
    panel.style.maxHeight = panel.scrollHeight + "px";
});
</script>
