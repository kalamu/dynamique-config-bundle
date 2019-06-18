<?php

/*
 * This file is part of the kalamu/dynamique-config-bundle package.
 *
 * (c) ETIC Services
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kalamu\DynamiqueConfigBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Form\FormError;

class DynamiqueConfiguratorController extends Controller
{

    public function indexAction(Request $Request, $part = null){
        $configurators = $this->getParameter('kalamu_dynamique_config.configurators');

        if(!$part || !isset($configurators[$part])){
            $part = key($configurators);
        }

        if($part){
            $Request->attributes->set('part', $part);
            return $this->forward($configurators[$part]['controller'], array('part' => $part));
        }

        return $this->render($this->getParameter('kalamu_dynamique_config.base_template'));
    }

    public function importExportAction(Request $Request){
        $import_form = $this->getImportForm();

        $import_form->handleRequest($Request);
        if($import_form->isValid()){
            $file = $import_form->get('file')->getData();
            try{
                $config = Yaml::parse(file_get_contents($file->getRealPath()), true);
                $configContainer = $this->get('kalamu_dynamique_config');
                foreach($config as $key => $val){
                    $configContainer->set($key, $val);
                }

                $this->addFlash('success', "The configuration has been imported.");
                return $this->redirect($this->generateUrl('dynamique_configurator', array('part' => '_export_config')));
            }catch(ParseException $e){
                $import_form->get('file')->addError(new FormError($e->getMessage()));
            }
        }

        $base_template = $this->getParameter('kalamu_dynamique_config.base_template');
        return $this->render('KalamuDynamiqueConfigBundle:DynamiqueConfigurator:import_export.html.twig',
                array('base_template' => $base_template, 'import_form' => $import_form->createView() ));
    }

    /**
     * Download configuration as YAML
     * @return Response
     */
    public function exportAction(){
        $yaml_file = $this->getParameter('kalamu_dynamique_config.file');

        $response = new Response(is_file($yaml_file) ? file_get_contents($yaml_file) : '');
        $response->headers->set('Content-Type', 'text/yaml; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachmentl; filename="dynamique_config.yml"');
        return $response;
    }

    /**
     * Render the list of available configurators
     */
    public function asideAction(){
        $configurators = $this->getParameter('kalamu_dynamique_config.configurators');

        $master_request = $this->get('request_stack')->getMasterRequest();
        $part = $master_request->attributes->has('part') ? $master_request->attributes->get('part') : null;

        return $this->render('KalamuDynamiqueConfigBundle:DynamiqueConfigurator:aside.html.twig', array('configurators' => $configurators, 'part' => $part));
    }

    protected function getImportForm(){
        return $this->createFormBuilder()
                ->add('file', 'file', array('required' => true, 'constraints' => array(
                    new File(array('mimeTypes' => 'text/plain'))
                )))
                ->add('submit', 'submit', array('label' => 'Enregistrer', 'attr' => array('class' => 'btn btn-success')))
                ->getForm();
    }

}