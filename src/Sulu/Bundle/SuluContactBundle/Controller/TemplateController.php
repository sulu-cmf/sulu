<?php

namespace Sulu\Bundle\ContactBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class TemplateController extends Controller
{
    public function contactFormAction()
    {
        return $this->render('SuluContactBundle:Template:contact.form.html.twig', array());
    }

}
