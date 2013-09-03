<?php

namespace Sulu\Bundle\ContactBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class TemplateController extends Controller
{
	/**
	 * Returns the form for contacts
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function contactFormAction()
	{
		return $this->render('SuluContactBundle:Template:contact.form.html.twig', $this->getRenderArray());
	}

	/**
	 * Returns the form for accounts
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function accountFormAction()
	{
		return $this->render('SuluContactBundle:Template:account.form.html.twig', $this->getRenderArray());
	}

	/**
	 * Returns an array for rendering a form
	 * @return array
	 */
	private function getRenderArray()
	{
		$values = $this->getValues();
		$defaults = $this->getDefaults();

		return array(
			'addressTypes' => $values['addressTypes'],
			'phoneTypes' => $values['phoneTypes'],
			'emailTypes' => $values['emailTypes'],
			'urlTypes' => $values['urlTypes'],
			'countries' => $values['countries'],
			'defaultPhoneType' => $defaults['phoneType'],
			'defaultEmailType' => $defaults['emailType'],
			'defaultAddressType' => $defaults['addressType'],
			'defaultUrlType' => $defaults['urlType'],
			'defaultCountry' => $defaults['country']
		);
	}

	/**
	 * Returns the possible values for the dropdowns
	 * @return array
	 */
	private function getValues()
	{
		$values = array();

		$emailTypeEntity = 'SuluContactBundle:EmailType';
		$values['emailTypes'] = $this->getDoctrine($emailTypeEntity)
			->getRepository($emailTypeEntity)
			->findAll();

		$phoneTypeEntity = 'SuluContactBundle:PhoneType';
		$values['phoneTypes'] = $this->getDoctrine()
			->getRepository($phoneTypeEntity)
			->findAll();

		$addressTypeEntity = 'SuluContactBundle:AddressType';
		$values['addressTypes'] = $this->getDoctrine()
			->getRepository($addressTypeEntity)
			->findAll();

		$values['urlTypes'] = $this->getDoctrine()
			->getRepository('SuluContactBundle:UrlType')
			->findAll();

		$values['countries'] = $this->getDoctrine()
			->getRepository('SuluContactBundle:Country')
			->findAll();

		return $values;
	}

	/**
	 * Returns the default values for the dropdowns
	 * @return array
	 */
	private function getDefaults()
	{
		$config = $this->container->getParameter('sulu_contact.defaults');
		$defaults = array();

		$emailTypeEntity = 'SuluContactBundle:EmailType';
		$defaults['emailType'] = $this->getDoctrine($emailTypeEntity)
			->getRepository($emailTypeEntity)
			->find($config['emailType']);

		$phoneTypeEntity = 'SuluContactBundle:PhoneType';
		$defaults['phoneType'] = $this->getDoctrine()
			->getRepository($phoneTypeEntity)
			->find($config['phoneType']);

		$addressTypeEntity = 'SuluContactBundle:AddressType';
		$defaults['addressType'] = $this->getDoctrine()
			->getRepository($addressTypeEntity)
			->find($config['addressType']);

		$urlTypeEntity = 'SuluContactBundle:UrlType';
		$defaults['urlType'] = $this->getDoctrine()
			->getRepository($urlTypeEntity)
			->find($config['urlType']);

		$countryEntity = 'SuluContactBundle:Country';
		$defaults['country'] = $this->getDoctrine()
			->getRepository($countryEntity)
			->find($config['country']);

		return $defaults;
	}
}
