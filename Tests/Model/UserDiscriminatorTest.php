<?php

namespace PUGX\MultiUserBundle\Tests\Model;

use PUGX\MultiUserBundle\Model\UserDiscriminator;
use PUGX\MultiUserBundle\Tests\Stub\AnotherUser;
use PUGX\MultiUserBundle\Tests\Stub\User;

class UserDiscriminatorTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')->disableOriginalConstructor()->getMock();

        $this->event = $this->getMockBuilder('Symfony\Component\Security\Http\Event\InteractiveLoginEvent')->disableOriginalConstructor()->getMock();
        $this->token = $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken')->disableOriginalConstructor()->getMock();
        $this->user = new User();
        $this->userInvalid = $this->getMockBuilder('InvalidUser')->disableOriginalConstructor()->getMock();
        $this->userFactory = $this->getMockBuilder('PUGX\MultiUserBundle\Model\UserFactoryInterface')->disableOriginalConstructor()->getMock();

        $userParameters = [
            'entity' => [
                'class' => 'PUGX\MultiUserBundle\Tests\Stub\User',
                'factory' => 'PUGX\MultiUserBundle\Model\UserFactory',
            ],
            'registration' => [
                'form' => [
                    'type' => 'PUGX\MultiUserBundle\Tests\Stub\UserRegistrationForm',
                    'name' => 'fos_user_registration_form',
                    'validation_groups' => ['Registration', 'Default'],
                ],
                'template' => 'AcmeUserBundle:Registration:user_one.form.html.twig',
            ],
            'profile' => [
                'form' => [
                    'type' => 'PUGX\MultiUserBundle\Tests\Stub\UserProfileForm',
                    'name' => 'fos_user_profile_form',
                    'validation_groups' => ['Profile', 'Default'],
                ],
                'template' => 'AcmeUserBundle:Profile:user_two.form.html.twig',
            ],
        ];

        $anotherUserParameters = [
            'entity' => [
                'class' => 'PUGX\MultiUserBundle\Tests\Stub\AnotherUser',
                'factory' => 'PUGX\MultiUserBundle\Tests\Stub\CustomUserFactory',
            ],
            'registration' => [
                'form' => [
                    'type' => 'PUGX\MultiUserBundle\Tests\Stub\AnotherUserRegistrationForm',
                    'name' => 'fos_user_my_registration_form',
                    'validation_groups' => ['MyRegistration', 'Default'],
                ],
                'template' => 'AcmeUserBundle:Registration:user_two.form.html.twig',
            ],
            'profile' => [
                'form' => [
                    'type' => 'PUGX\MultiUserBundle\Tests\Stub\AnotherUserProfileForm',
                    'name' => 'fos_user_profile_form',
                    'validation_groups' => ['Profile', 'Default'],
                ],
                'template' => 'AcmeUserBundle:Profile:user_two.form.html.twig',
            ],
        ];

        $this->parameters = ['user_one' => $userParameters, 'user_two' => $anotherUserParameters];

        $this->discriminator = new UserDiscriminator($this->session, $this->parameters);
    }

    /**
     * @expectedException \LogicException
     */
    public function testBuildException()
    {
        $userParameters = [
            'entity' => [
                'class' => 'FakeUser',
                'factory' => 'PUGX\MultiUserBundle\Model\UserFactory',
            ],
            'registration' => [
                'form' => 'PUGX\MultiUserBundle\Tests\Stub\UserRegistrationForm',
                'options' => [
                    'validation_groups' => ['Registration', 'Default'],
                ],
            ],
            'profile' => [
                'form' => 'PUGX\MultiUserBundle\Tests\Stub\UserProfileForm',
                'options' => [
                    'validation_groups' => ['Profile', 'Default'],
                ],
            ],
        ];

        $parameters = ['user' => $userParameters];
        $discriminator = new UserDiscriminator($this->session, $parameters);
    }

    public function testGetClasses()
    {
        $this->assertEquals(['PUGX\MultiUserBundle\Tests\Stub\User', 'PUGX\MultiUserBundle\Tests\Stub\AnotherUser'], $this->discriminator->getClasses());
    }

    /**
     * @expectedException \LogicException
     */
    public function testSetClassException()
    {
        $this->discriminator->setClass('ArbitaryClass');
    }

    public function testGetClass()
    {
        $this->discriminator->setClass('PUGX\MultiUserBundle\Tests\Stub\AnotherUser');
        $this->assertEquals('PUGX\MultiUserBundle\Tests\Stub\AnotherUser', $this->discriminator->getClass());
    }

    public function testSetClassPersist()
    {
        $this->session->expects($this->exactly(1))->method('set')->with(UserDiscriminator::SESSION_NAME, 'PUGX\MultiUserBundle\Tests\Stub\User');
        $this->discriminator->setClass('PUGX\MultiUserBundle\Tests\Stub\User', true);
    }

    public function testGetClassDefault()
    {
        $this->session->expects($this->exactly(1))->method('get')->with(UserDiscriminator::SESSION_NAME, null)->will($this->onConsecutiveCalls(null));
        $this->assertEquals('PUGX\MultiUserBundle\Tests\Stub\User', $this->discriminator->getClass());
    }

    public function testGetClassStored()
    {
        $this->session->expects($this->exactly(1))->method('get')->with(UserDiscriminator::SESSION_NAME, null)->will($this->onConsecutiveCalls('PUGX\MultiUserBundle\Tests\Stub\AnotherUser'));
        $this->assertEquals('PUGX\MultiUserBundle\Tests\Stub\AnotherUser', $this->discriminator->getClass());
    }

    public function testCreateUser()
    {
        $expected = new AnotherUser();
        $this->session->expects($this->exactly(0))->method('get');

        $this->discriminator->setClass('PUGX\MultiUserBundle\Tests\Stub\AnotherUser');
        $result = $this->discriminator->createUser();
        $this->assertEquals($expected, $result);
    }

    public function testGetUserFactory()
    {
        $this->discriminator->setClass('PUGX\MultiUserBundle\Tests\Stub\AnotherUser');
        $result = $this->discriminator->getUserFactory();
        $this->assertEquals('PUGX\MultiUserBundle\Tests\Stub\CustomUserFactory', $result);
    }

    public function testGetFormTypeRegistration()
    {
        $this->discriminator->setClass('PUGX\MultiUserBundle\Tests\Stub\User');
        $result = $this->discriminator->getFormType('registration');
        $this->assertEquals('PUGX\MultiUserBundle\Tests\Stub\UserRegistrationForm', get_class($result));
    }

    public function testGetFormTypeProfile()
    {
        $this->discriminator->setClass('PUGX\MultiUserBundle\Tests\Stub\User');
        $result = $this->discriminator->getFormType('profile');
        $this->assertEquals('PUGX\MultiUserBundle\Tests\Stub\UserProfileForm', get_class($result));
    }

    public function testGetFormNameRegistration()
    {
        $this->discriminator->setClass('PUGX\MultiUserBundle\Tests\Stub\AnotherUser');
        $result = $this->discriminator->getFormName('registration');
        $this->assertEquals('fos_user_my_registration_form', $result);
    }

    public function testGetFormNameProfile()
    {
        $this->discriminator->setClass('PUGX\MultiUserBundle\Tests\Stub\User');
        $result = $this->discriminator->getFormName('profile');
        $this->assertEquals('fos_user_profile_form', $result);
    }

    public function testGetValidationGroupsRegistration()
    {
        $this->discriminator->setClass('PUGX\MultiUserBundle\Tests\Stub\User');
        $result = $this->discriminator->getFormValidationGroups('registration');
        $this->assertEquals(['Registration', 'Default'], $result);
    }

    public function testGetValidationGroupsRegistrationCustom()
    {
        $this->discriminator->setClass('PUGX\MultiUserBundle\Tests\Stub\AnotherUser');
        $result = $this->discriminator->getFormValidationGroups('registration');
        $this->assertEquals(['MyRegistration', 'Default'], $result);
    }

    public function testGetValidationGroupsProfile()
    {
        $this->discriminator->setClass('PUGX\MultiUserBundle\Tests\Stub\User');
        $result = $this->discriminator->getFormValidationGroups('profile');
        $this->assertEquals(['Profile', 'Default'], $result);
    }

    public function testGetRegistrationTemplate()
    {
        $this->discriminator->setClass('PUGX\MultiUserBundle\Tests\Stub\User');
        $result = $this->discriminator->getTemplate('registration');
        $this->assertEquals('AcmeUserBundle:Registration:user_one.form.html.twig', $result);
    }
}
