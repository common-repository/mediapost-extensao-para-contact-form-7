<?php
/*
Plugin Name: @MediaPost - Extensão para Contact Form 7
Description: Com este plugin você poderá integrar seus formulários do Contact Form 7 com sua conta @MediaPost.
Author: @MediaPost
Version: 1.0.1
Author URI: https://www.mediapost.com.br/
Text Domain: cf7_mediapost_extensao
*/
defined('ABSPATH') or exit;

define('MP_CF7_DIR', plugin_dir_path(__FILE__));
define('MP_CF7_URL', plugins_url('', __FILE__));
define('MP_CF7_PREFIX', 'cf7mediapost');

// Verifica se o plugin Contact Form 7 está instalado e ativo, caso contrário exibe um aviso
function cf7_mp_error()
{
    if (!file_exists(WP_PLUGIN_DIR . '/contact-form-7/wp-contact-form-7.php')) {
        $cf7_mp_error_out = '<div id="message" class="error notice is-dismissible"><p>';
        $cf7_mp_error_out .= __('O plugin Contact Form 7 precisa estar instalado para que <b>@MediaPost - Extensão para Contact Form 7</b> funcione. <b><a href="' . admin_url('plugin-install.php?tab=plugin-information&plugin=contact-form-7&from=plugins&TB_iframe=true&width=600&height=550') . '" class="thickbox" title="Contact Form 7">Clique aqui para instalar o plugin Contact Form 7</a>.</b>', 'cf7_mp_error');
        $cf7_mp_error_out .= '</p></div>';
        echo $cf7_mp_error_out;
    } elseif (!class_exists('WPCF7')) {
        $cf7_mp_error_out = '<div id="message" class="error notice is-dismissible"><p>';
        $cf7_mp_error_out .= __('Para que o plugin O plugin "@MediaPost - Extensão para Contact Form 7" funcione, <b>ative o plugin Contact Form 7</b>.', 'cf7_mp_error');
        $cf7_mp_error_out .= '</p></div>';
        echo $cf7_mp_error_out;
    }
}
add_action('admin_notices', 'cf7_mp_error');

// Verifica se o plugin Contact Form 7 está ativo
include_once(ABSPATH . 'wp-admin/includes/plugin.php');
if (is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
    // Insere links após o plugin estar ativo
    function cf7mediapost_action_links($links)
    {
        $links[] = '<a href="' . admin_url('admin.php?page=wpcf7&post='. get_latest_cf7_form() . '&active-tab=4') . '">' . esc_attr__("Configurar", "cf7-mp-ext") . '</a>';
        $links[] = '<a href="' . esc_url('https://www.mediapost.com.br/criar-conta?como_conheceu=Plugin%20CF7%20Wordpress') . '" target="_blank">' . esc_attr__( "Criar conta", "cf7-mp-ext" ) . '</a>';
        return $links;
    }
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'cf7mediapost_action_links');

    // Enqueue JS e CSS na administração
    function cf7mediapost_enqueue($hook)
    {
        if (!strpos($hook, 'wpcf7')) {
            return;
        }

        wp_enqueue_style( 'cf7mediapost-styles', MP_CF7_URL . '/assets/css/styles.css', false);
        wp_enqueue_script( 'cf7mediapost-scripts', MP_CF7_URL . '/assets/js/scripts.js', array('jquery'));
    }
    add_action('admin_enqueue_scripts', 'cf7mediapost_enqueue');

    // Verifica se a classe da API @MediaPost existe
    if (!class_exists("Mapi\Client")) {
        //Map de Cliente
        include_once(MP_CF7_DIR . "/includes/client/oauth/OAuth.php");
        include_once(MP_CF7_DIR . "/includes/client/Helper/ContainerAbstract.php");
        include_once(MP_CF7_DIR . "/includes/client/Exception.php");
        include_once(MP_CF7_DIR . "/includes/client/Response.php");
        include_once(MP_CF7_DIR . "/includes/client/Client.php");
        include_once(MP_CF7_DIR . "/includes/client/Request/Config.php");
        include_once(MP_CF7_DIR . "/includes/client/Request/Request.php");
    }

    // Insere a aba d0 @MediaPost nas configurações do formulário
    function cf7mediapost_admin_panel($panels)
    {
        $new_page = array(
            'cf7mediapost' => array(
                'title'    => __('@MediaPost', 'contact-form-7'),
                'callback' => 'cf7mediapost_admin_panel_content'
            )
        );

        $panels = array_merge($panels, $new_page);
        return $panels;
    }
    add_filter('wpcf7_editor_panels', 'cf7mediapost_admin_panel');

    // Salvar dados da integração no formulário quando o formulário for salvo
    function cf7mediapost_admin_save_form($cf7)
    {
        $post_id = sanitize_text_field($_GET['post']);

        // Recupera keys salvas no formulário
        $consumer_key    = get_post_meta($post_id, "_cf7mediapost_consumer_key", true);
        $consumer_secret = get_post_meta($post_id, "_cf7mediapost_consumer_secret", true);
        $token           = get_post_meta($post_id, "_cf7mediapost_token", true);
        $token_secret    = get_post_meta($post_id, "_cf7mediapost_token_secret", true);

        //Obter a lista de campos enviados
        if ($consumer_key && $consumer_secret && $token && $token_secret) {
            $mapi = new Mapi\Client($consumer_key, $consumer_secret, $token, $token_secret);

            $arrResult = cf7mediapost_get_mp_fields($mapi);
            if ($arrResult['status']) {
                foreach ($arrResult['response'] as $campo_id => $campo_nome) {
                    update_post_meta($post_id, '_cf7mediapost_campo_' . $campo_id, $_POST['cf7mediapost_campo_' . $campo_id]);
                }
            } else {
                $erro = $arrResult['response'];
            }
        }

        //Salvar campos de credenciais
        update_post_meta($post_id, '_cf7mediapost_consumer_key', (isset($_POST['cf7mediapost_consumer_key']) ? $_POST['cf7mediapost_consumer_key'] : ''));
        update_post_meta($post_id, '_cf7mediapost_consumer_secret', (isset($_POST['cf7mediapost_consumer_secret']) ? $_POST['cf7mediapost_consumer_secret'] : ''));
        update_post_meta($post_id, '_cf7mediapost_token', (isset($_POST['cf7mediapost_token']) ? $_POST['cf7mediapost_token'] : ''));
        update_post_meta($post_id, '_cf7mediapost_token_secret', (isset($_POST['cf7mediapost_token_secret']) ? $_POST['cf7mediapost_token_secret'] : ''));
        update_post_meta($post_id, '_cf7mediapost_enabled', (isset($_POST['cf7mediapost_enabled']) ? $_POST['cf7mediapost_enabled'] : ''));
        update_post_meta($post_id, '_cf7mediapost_lista', (isset($_POST['cf7mediapost_lista']) ? $_POST['cf7mediapost_lista'] : ''));
        update_post_meta($post_id, '_cf7mediapost_form_fields', (isset($_POST['cf7mediapost_form_fields']) ? $_POST['cf7mediapost_form_fields'] : ''));
    }
    add_action('wpcf7_save_contact_form', 'cf7mediapost_admin_save_form');

    // Detectar envio de dados vindos do Front-End
    function cf7mediapost_frontend_submit_form($wpcf7_data)
    {
        $post_id = $wpcf7_data->id;
        $mediapost_plugin_ativado = get_post_meta($post_id, "_cf7mediapost_enabled", true);

        if (1 == $mediapost_plugin_ativado) {
            $consumer_key    = get_post_meta($post_id, "_cf7mediapost_consumer_key", true);
            $consumer_secret = get_post_meta($post_id, "_cf7mediapost_consumer_secret", true);
            $token           = get_post_meta($post_id, "_cf7mediapost_token", true);
            $token_secret    = get_post_meta($post_id, "_cf7mediapost_token_secret", true);
            $lista           = get_post_meta($post_id, "_cf7mediapost_lista", true);

            if ($consumer_key && $consumer_secret && $token && $token_secret && $lista) {
                $submission = WPCF7_Submission::get_instance();
                $posted_data = $submission->get_posted_data();

                // Criar array do contato para enviar para o @MediaPost
                $arrContato = array();
                $arrContato['lista'] = $lista;
                $mp_campos = get_post_meta($post_id, "_cf7mediapost_form_fields", true);
                $mp_campos = json_decode(base64_decode($mp_campos), true);

                foreach ($mp_campos as $campo_id => $campo_nome) {
                    $campo_valor = get_post_meta($post_id, "_cf7mediapost_campo_" . $campo_id, true);
                    $valor_obtido_formulario = cf7mediapost_campos_filtrar($campo_valor, $posted_data);
                    $arrContato['contato'][0][$campo_id] = $valor_obtido_formulario;
                }

                if (!empty($arrContato['contato'][0]["email"])) {
                    $mapi = new Mapi\Client($consumer_key, $consumer_secret, $token, $token_secret);

                    try {
                        $arrResult = $mapi->put("contato/salvar", $arrContato);
                    } catch (MapiException $e) {
                        $erro = $e->getMessage();
                    }
                }
            }
        }
    }
    add_action("wpcf7_before_send_mail", "cf7mediapost_frontend_submit_form");

    // Retorna o último formulário criado no Contact Form 7
    function get_latest_cf7_form()
    {
        $args = array(
                'post_type'      => 'wpcf7_contact_form',
                'posts_per_page' => -1,
                'fields'         => 'ids',
            );
        // Get Highest Value from CF7Forms
        $form = max(get_posts($args));
        $out = '';
        if (!empty($form)) {
            $out .= $form;
        }
        return $out;
    }

    // Chamar conteúdo da aba @MediaPost
    function cf7mediapost_admin_panel_content($cf7)
    {
        $post_id = sanitize_text_field($_GET['post']);

        $enabled         = get_post_meta($post_id, "_cf7mediapost_enabled", true);
        $consumer_key    = get_post_meta($post_id, "_cf7mediapost_consumer_key", true);
        $consumer_secret = get_post_meta($post_id, "_cf7mediapost_consumer_secret", true);
        $token           = get_post_meta($post_id, "_cf7mediapost_token", true);
        $token_secret    = get_post_meta($post_id, "_cf7mediapost_token_secret", true);
        $mp_conta        = get_post_meta($post_id, "_cf7mediapost_conta", true);
        $lista           = get_post_meta($post_id, "_cf7mediapost_lista", true);

        if ($consumer_key && $consumer_secret && $token && $token_secret) {
            //Obter a lista de contatos da MediaPost
            $mapi = new Mapi\Client($consumer_key, $consumer_secret, $token, $token_secret);

            $mp_conta = cf7mediapost_get_mp_account_name($mapi);

            if ($mp_conta['status']) {
                $api_status = "success";
                $mp_fields = cf7mediapost_get_mp_fields($mapi);

                $html_status = '
                    <hr/>
                    <div class="accordion"><h2><span class="rounded ' . ($enabled ? "success" : "warning") . '">2</span>Ativar a integração para este formulário?</h2></div>
                    <div class="panel">
                        <p>Marque a caixa abaixo para ativar a integração deste formulário com sua conta @MediaPost.</p>
                        ' . (!$enabled ? '<div class="alerta_aviso">Ative a integração deste formulário para que os dados sejam salvos em sua conta @MediaPost.</div>' : '') . '
                        <table class="form-table">
                            <tbody>
                                <tr>
                                    <th scope="row">Ativar integração</th>
                                    <td>
                                        <label><input type="radio" name="cf7mediapost_enabled" value="1" ' . ($enabled == 1 ? ' checked' : '') . '> Sim</label>
                                        <label><input type="radio" name="cf7mediapost_enabled" value="0" ' . ($enabled == 0 ? ' checked' : '') . '> Não</label>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                ';

                $mp_listas = cf7mediapost_get_mp_lists($mapi);
                $html_listas = '
                    <hr/>
                    <div class="accordion"><h2><span class="rounded ' . ($enabled ? ($lista ? "success" : "warning") : "") . '">3</span>Lista de contatos</h2></div>
                    <div class="panel">
                        <p>Selecione a lista de contatos onde os cadastros devem ser salvos na sua conta @MediaPost.</p>
                ';
                if ($mp_listas['status']) {
                    $html_listas .= (empty($lista) ? '<div class="alerta_aviso">Escolha uma lista @MediaPost onde os contatos serão salvos.</div>' : '') . '
                        <table class="form-table">
                            <tbody>
                                <tr>
                                    <th scope="row">Lista</th>
                                    <td>
                                        <select name="cf7mediapost_lista">
                                        <option value="">Selecione uma lista</option>
                    ';

                    foreach ($mp_listas['response'] as $key => $value) {
                        $html_listas .= '<option value="' . $value['cod'] . '"' . ($value['cod'] == $lista ? "selected" : "") . '>' . $value['nome'] . '</option>';
                    }

                    $html_listas .= '
                                            </select>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    ';
                } else {
                    $html_listas .= '<div class="alerta_erro">Nenhuma lista encontrada. Crie uma lista em sua conta @MediaPost e salve este formulário novamente para que ela apareça.</div></div>';
                }

                $campo_email = get_post_meta($post_id, "_cf7mediapost_campo_email", true);
                $html_campos = '
                    <hr/>
                    <div class="accordion"><h2><span class="rounded ' . ($enabled ? ($campo_email ? "success" : "warning") : "") . '">4</span>Relacionar campos</h2></div>
                    <div class="panel">
                        <p>Relacione os campos de seu formulário com os campos de sua conta @MediaPost.</p>
                        ' . (empty($campo_email) ? '<div class="alerta_aviso">Relacione o campo e-mail. Sem ele a integração não irá funcionar.</div>' : '') . '
                ';
                if ($mp_fields['status']) {
                    $html_campos .= '
                        <table class="form-table">
                            <tbody>
                    ';
                    foreach ($mp_fields['response'] as $campo_id => $campo_nome) {
                        $search_replace['{campo_' . $campo_id . '}'] = $campo_nome;

                        $campo_{$campo_id} = get_post_meta($post_id, "_cf7mediapost_campo_" . $campo_id, true);
                        $html_campos .= '
                            <tr>
                                <th scope="row">' . $campo_nome . '</th>
                                <td>
                                    <input type="text" name="cf7mediapost_campo_' . $campo_id . '" class="large-text code" value="' . $campo_{$campo_id} . '" />
                                    <div>' . cf7mediapost_mail_tags() . '</div>
                                </td>
                            </tr>
                        ';
                    }
                    $html_campos .= '
                                </tbody>
                            </table>
                        </div>
                    ';
                } else {
                    $html_campos .= '<div class="alerta_erro">' . $mp_fields['response'] . '</div>';
                }
            } else {
                $api_active = "load_active";
            }
        } else {
            $api_active = "load_active";
        }

        $search_replace = array(
            '{api_status}'       => (isset($api_status) ? $api_status : ''),
            '{api_active}'       => (isset($api_active) ? $api_active : ''),
            '{html_status}'      => (isset($html_status) ? $html_status : ''),
            '{html_listas}'      => (isset($html_listas) ? $html_listas : ''),
            '{html_campos}'      => (isset($html_campos) ? $html_campos : ''),
            '{consumer_key}'     => $consumer_key,
            '{consumer_secret}'  => $consumer_secret,
            '{token}'            => $token,
            '{token_secret}'     => $token_secret,
            '{tags_utilizadas}'  => cf7mediapost_mail_tags(),
            '{erro}'             => (isset($erro) ? $erro : ''),
            '{mp_conta}'         => (!isset($mp_conta['status']) ? '' : ($mp_conta['status'] ? '<div class="alerta_sucesso">Conectado na conta ' . $mp_conta['response'] . '</div>' : '<div class="alerta_erro">' . $mp_conta['response'] . '</div>')),
            '{mp_fields_hidden}' => (isset($mp_fields['response']) ? base64_encode(json_encode($mp_fields['response'])) : '') // //Obter todos os campos e guardar para evitar 1 request a mais na API além do cadastro do contato ao enviar os dados do formulário
        );

        $search  = array_keys($search_replace);
        $replace = array_values($search_replace);

        $template = cf7mediapost_get_view_template('ui-tabs-panel.tpl.php');

        $admin_table_output = str_replace($search, $replace, $template);
        echo $admin_table_output;
    }

    // ADM: Pegar template da View
    function cf7mediapost_get_view_template($template_name)
    {
        $template_content = false;
        $template_path = MP_CF7_DIR . 'views/' . $template_name;
        if (file_exists($template_path)) {
            $search_replace = array(
                "<?php defined('ABSPATH') or exit; ?>" => '',
            );

            $search  = array_keys($search_replace);
            $replace = array_values($search_replace);

            $template_content = str_replace($search, $replace, file_get_contents($template_path));
        }
        return $template_content;
    }

    // Obter Tags utilizadas no formulário
    function cf7mediapost_wpcf7_form_tags()
    {
        $manager = class_exists('WPCF7_FormTagsManager') ? WPCF7_FormTagsManager::get_instance() : WPCF7_ShortcodeManager::get_instance();
        $form_tags = $manager->get_scanned_tags();
        return $form_tags;
    }

    // Obter os campos do cadastro do contato @MediaPost
    function cf7mediapost_get_mp_fields($mapi)
    {
        if (!empty($mapi)) {
            try {
                $arrResult = $mapi->get("contato/campos");
                $arrResultCampos = $arrResult['result'];

                unset($arrResultCampos['conta']); //Remover o campo "Conta"
                $arrResultCampos["email"] = $arrResultCampos["email"] . " (obrigatório)"; //Definir campo e-mail como obrigatório

                //Deixar os principais campos em primeiro
                $OrdemDosCampos = array(
                    'nome',
                    'email',
                    'telefone',
                    'celular',
                );

                $arraySalvarCampo = array();
                foreach ($OrdemDosCampos as $campo_organizado_id) {
                    $arraySalvarCampo[$campo_organizado_id] = $arrResultCampos[$campo_organizado_id];
                    unset($arrResultCampos[$campo_organizado_id]);
                }

                $arr_result = array(
                    'status' => true,
                    'response' => array_merge($arraySalvarCampo, $arrResultCampos)
                );
            } catch (MapiException $e) {
                $arr_result = array(
                    'status' => false,
                    'response' => $e->getMessage()
                );
            } catch (Exception $e) {
                $arr_result = array(
                    'status' => false,
                    'response' => $e->getMessage()
                );
            }
        } else {
            $arr_result = array(
                'status' => false,
                'response' => 'Credenciais de integração não foram informadas.'
            );
        }

        return $arr_result;
    }

    // Obter o nome da conta @MediaPost
    function cf7mediapost_get_mp_account_name($mapi)
    {
        if (!empty($mapi)) {
            try {
                $arrResult = $mapi->get("contato/campos");

                $arr_result = array(
                    'status'   => true,
                    'response' => $arrResult['result']['conta']
                );
            } catch (MapiException $e) {
                $arr_result = array(
                    'status' => false,
                    'response' => $e->getMessage()
                );
            } catch (Exception $e) {
                $arr_result = array(
                    'status' => false,
                    'response' => $e->getMessage()
                );
            }
        } else {
            $arr_result = array(
                'status'   => false,
                'response' => 'Credenciais de integração não foram informadas.'
            );
        }

        return $arr_result;
    }

    // Obter as listas da conta @MediaPost
    function cf7mediapost_get_mp_lists($mapi)
    {
        if (!empty($mapi)) {
            try {
                $arrResult = $mapi->get("lista/all");

                $arr_result = array(
                    'status' => true,
                    'response' => $arrResult['result']
                );
            } catch (MapiException $e) {
                $arr_result = array(
                    'status' => false,
                    'response' => $e->getMessage()
                );
            } catch (Exception $e) {
                $arr_result = array(
                    'status' => false,
                    'response' => $e->getMessage()
                );
            }
        } else {
            $arr_result = array(
                'status' => false,
                'response' => 'Credenciais de integração não foram informadas.'
            );
        }

        return $arr_result;
    }

    // Separar as tags utilizadas no formulário
    function cf7mediapost_mail_tags()
    {
        $listatags = cf7mediapost_wpcf7_form_tags();
        $tag_submit = array_pop($listatags);
        $tagInfo = '';
        $arrayTagsRetornar = array();

        foreach ($listatags as $tag) {
            if (trim($tag['name'])) {
                $arrayTagsRetornar[] = '<a class="tags-utilizar" data-tag="[' . $tag['name'] . ']" href="javascript:void(0);">[' . $tag['name'] . ']</a>';
            }
        }

        if (is_array($arrayTagsRetornar) && count($arrayTagsRetornar) > 0) {
            $tagInfo .= implode('&nbsp;&nbsp;', $arrayTagsRetornar);
        }

        if ($tagInfo) {
            $tagInfo .= " <-- Você pode utilizar essas tags";
        }

        return $tagInfo;
    }

    function cf7mediapost_campos_filtrar($valor, $arrayData)
    {
        if (is_array($arrayData) && count($arrayData) > 0) {
            foreach ($arrayData as $campo_nome => $campo_valor) {
                $valor = str_replace('[' . $campo_nome . ']', utf8_decode($campo_valor), $valor); //Substitui tag e decodifica o UTF8
            }
        }
        return trim($valor);
    }
}
