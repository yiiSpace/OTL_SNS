<?php

class SiteController extends Controller
{
	/**
	 * @return array action filters
	 */
	public function filters()
	{
		return array(
			'accessControl',
		);
	}

	/**
	 * Specifies the access control rules.
	 */
	public function accessRules()
	{
		return array(
			array('allow', // the create action can only be executed by a local user
				'actions'=>array('create'),
				'ips'=>array('127.0.0.1'),
			),
			array('allow', // allow all users to perform all other actions
				'users'=>array('*'),
			),
		);
	}

	/**
	 * Declares class-based actions.
	 */
	public function actions()
	{
		return array(
			// captcha action renders the CAPTCHA image displayed on the contact page
			'captcha'=>array(
				'class'=>'CCaptchaAction',
				'backColor'=>0xFFFFFF,
			),
			// page action renders "static" pages stored under 'protected/views/site/pages'
			// They can be accessed via: index.php?r=site/page&view=FileName
			'page'=>array(
				'class'=>'CViewAction',
			),
		);
	}

	/**
	 * This is the default 'index' action that is invoked
	 * when an action is not explicitly requested by users.
	 */
	public function actionIndex()
	{
		// renders the view file 'protected/views/site/index.php'
		// using the default layout 'protected/views/layouts/index.php'
		$this->layout = 'index';
		//$this->render('index');
		$model=new LoginForm;

		// if it is ajax validation request
		if(isset($_POST['ajax']) && $_POST['ajax']==='login-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}

		// collect user input data
		if(isset($_POST['LoginForm']))
		{
			$model->attributes=$_POST['LoginForm'];
			// validate user input and redirect to the previous page if valid
			if($model->validate() && $model->login())
				$this->redirect(Yii::app()->baseUrl."/index.php/user");
		}
		// display the login form
		$this->render('index',array('model'=>$model));
	}

	/**
	 * This is the action to handle search operations in the site.
	 * It would render a normal search page if a un-ajax request is passed,
	 * or simply return an array of results if the request is an ajax one.
	 * $items should be passed with 'courses' or 'users' (or both) key(s) if
	 * search on this(these) model(s) is(are) required.
	 */
	public function actionSearch($name, $items)
	{
		if (!isset($_POST['ajax'])) {
			//Render a search page?
		} else {
			$courses = array();
			$users = array();

			if (isset($items['courses']))
				$courses = Course::model()->find('COURSE_NAME=?', array($name));
			if (isset($items['users']))
				$users = User::model()->find('USER_NAME=?', array($name));

			return CJSON::encode(array(
				'courses' => $courses,
				'users' => $users,
				));
		}
	}

	/**
	 * This is the action to handle external exceptions.
	 */
	public function actionError()
	{
	    if($error=Yii::app()->errorHandler->error)
	    {
	    	if(Yii::app()->request->isAjaxRequest)
	    		echo $error['message'];
	    	else
	        	$this->render('error', $error);
	    }
	}

	/**
	 * Displays the contact page
	 */
	public function actionContact()
	{
		$model=new ContactForm;
		if(isset($_POST['ContactForm']))
		{
			$model->attributes=$_POST['ContactForm'];
			if($model->validate())
			{
				$headers="From: {$model->email}\r\nReply-To: {$model->email}";
				mail(Yii::app()->params['adminEmail'],$model->subject,$model->body,$headers);
				Yii::app()->user->setFlash('contact','Thank you for contacting us. We will respond to you as soon as possible.');
				$this->refresh();
			}
		}
		$this->render('contact',array('model'=>$model));
	}

	/**
	 * Displays the login page
	 */
	public function actionLogin()
	{
		// renders the view file 'protected/views/site/index.php'
		// using the default layout 'protected/views/layouts/index.php'
		$this->layout = 'index';
		//$this->render('index');
		$model=new LoginForm;

		// if it is ajax validation request
		if(isset($_POST['ajax']) && $_POST['ajax']==='login-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}

		// collect user input data
		if(isset($_POST['LoginForm']))
		{
			$model->attributes=$_POST['LoginForm'];
			// validate user input and redirect to the previous page if valid
			if($model->validate() && $model->login())
				$this->redirect(Yii::app()->baseUrl."/index.php/user");
		}
		// display the login form
		$this->render('index',array('model'=>$model));
	}

	/**
	 * Logs out the current user and redirect to homepage.
	 */
	public function actionLogout()
	{
		Yii::app()->user->logout();
		$this->redirect(Yii::app()->homeUrl);
	}

	/**
	 * The function to create roles of this application.
	 * It should be run one and only one time.
	 * Make sure you delete or comment this function before you get make this
	 * application public.
	 */
	public function actionCreateRoles()
	{
		$auth = Yii::app()->authManager;
		$bizRule = null;

		// Guests are those who have not been logged in yet.
		// $bizRule = 'return Yii->app()->user->isGuest;';
		$role = $auth->createRole('guest', 'guest user', $bizRule);

		// Authenticated users are those normal users who have successfully logged in.
		// $bizRule = 'return !Yii->app()->user->isGuest;';
		$role = $auth->createRole('authenticated', 'authenticated user', $bizRule);
		$role->addChild('guest'); // an authenticated user can do whatever a guest can do

		// Admins are those who can manage the website.
		// $bizRule = 'return User::model()->findByPk(Yii->app()->user->id)->ISADMIN == "Y";';
		$role = $auth->createRole('admin', 'administrator', $bizRule);
		$role->addChild('authenticated'); // an admin can do whatever an authenticated user can do

		echo "success!";
	}
}
