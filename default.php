<?php if (!defined('APPLICATION')) exit();

// Define the plugin:
$PluginInfo['DiscussionsCategoryFilter'] = array(
   'Name' => 'Discussions Category Filter',
   'Description' => 'Filters what categories to show on all discussions page.',
   'Version' => '1.0.1',
   'Author' => "Brandon Summers",
   'AuthorEmail' => 'brandon@evolutionpixels.com',
   'AuthorUrl' => 'http://www.evolutionpixels.com'
);

class DiscussionsCategoryFilterPlugin extends Gdn_Plugin {

	public function Base_GetAppSettingsMenuItems_Handler($Sender) {
		$Menu = &$Sender->EventArguments['SideMenu'];
		
		$LinkText = T('Discussions Category Filtering');
		$Menu->AddItem('Forum', T('Forum'));
		$Menu->AddLink('Forum', $LinkText, 'plugin/discussionscategoryfilter', 'Garden.Settings.Manage');
	}

	public function PluginController_DiscussionsCategoryFilter_Create($Sender)
	{
		$Sender->Title('Discussions: Category Filtering');
		$Sender->AddSideMenu('plugin/discussionscategoryfilter');
		$Sender->Form = new Gdn_Form();
		$this->Dispatch($Sender, $Sender->RequestArgs);
	}

	public function Controller_Index($Sender)
	{
		$Sender->AddCssFile('admin.css');
		$Sender->CategoryData = $this->GetAllCategories();
		
		$Sender->Render($this->GetView('discussionscategoryfilter.php'));
	}

	public function Controller_Disable($Sender)
	{
		$Arguments = $Sender->RequestArgs;
		
		if (sizeof($Arguments) != 2)
			return;
		
		list($Controller, $CategoryID) = $Arguments;

		Gdn::SQL()->Delete('Flag',array(
			'ForeignURL'      => $URL
		));
		Gdn::SQL()->Update('Category')
           ->Set('ShowInAllDiscussions', 0)
           ->Where('CategoryID', $CategoryID)
           ->Put();

		$this->Controller_Index($Sender);
	}

	public function Controller_Enable($Sender)
	{
		$Arguments = $Sender->RequestArgs;
		
		if (sizeof($Arguments) != 2)
			return;
		
		list($Controller, $CategoryID) = $Arguments;

		Gdn::SQL()->Update('Category')
           ->Set('ShowInAllDiscussions', 1)
           ->Where('CategoryID', $CategoryID)
           ->Put();

		$this->Controller_Index($Sender);
	}

	public function Controller_EnableAll($Sender)
	{
		Gdn::SQL()->Update('Category')
           ->Set('ShowInAllDiscussions', 1)
           ->Put();

		$this->Controller_Index($Sender);
	}

	public function DiscussionModel_BeforeGet_Handler($Sender)
	{
		if (Gdn::Dispatcher()->Application() == 'vanilla' 
			AND Gdn::Dispatcher()->ControllerName() == 'DiscussionsController' 
			AND ucfirst(Gdn::Dispatcher()->ControllerMethod()) == 'Index')
		{
			$Sender->SQL->Where('ca.ShowInAllDiscussions =', '1');
		}
	}

	public function DiscussionsController_AfterBuildPager_Handler($Sender)
	{
		if (Gdn::Dispatcher()->Application() == 'vanilla' 
			AND Gdn::Dispatcher()->ControllerName() == 'DiscussionsController' 
			AND ucfirst(Gdn::Dispatcher()->ControllerMethod()) == 'Index')
		{
			$DiscussionModel = new DiscussionModel();
			$CountDiscussions = $DiscussionModel->GetCount(array('c.ShowInAllDiscussions =' => '1'));
			$Sender->Pager->TotalRecords = is_numeric($CountDiscussions) ? $CountDiscussions : 0;

			// Override the Anouncements data. This is somewhat hackish, but there is no other way.
			$Arguments = Gdn::Dispatcher()->ControllerArguments();
			$Page = $Arguments[0];

			// Determine offset from $Page
			list($Page, $Limit) = OffsetLimit($Page, Gdn::Config('Vanilla.Discussions.PerPage', 30));

			// Validate $Page
			if (!is_numeric($Page) || $Page < 0)
				$Page = 0;

			$CategoryIDs = array();
			$Categories = Gdn::SQL()
				->Select('c.CategoryID, c.Name, c.CountDiscussions, c.AllowDiscussions, c.ShowInAllDiscussions')
				->From('Category c')
				->Where('c.ShowInAllDiscussions =', '0')
				->OrderBy('Sort', 'asc')
				->Get();

			foreach ($Categories as $Category)
			{
				$CategoryIDs[] = $Category->CategoryID;
			}

			$Sender->AnnounceData = $Page == 0 ? $DiscussionModel->GetAnnouncements(array('d.CategoryID <> ' => implode(' and d.CategoryID <> ', $CategoryIDs))) : FALSE;
			$Sender->SetData('Announcements', $Sender->AnnounceData !== FALSE ? $Sender->AnnounceData : array(), TRUE);
		}
	}

	public function GetAllCategories()
	{
		$Categories = Gdn::SQL()
			->Select('c.ParentCategoryID, c.CategoryID, c.Name, c.CountDiscussions, c.AllowDiscussions, c.ShowInAllDiscussions')
			->From('Category c')
			->OrderBy('Sort', 'asc');

		return $Categories->Get();
	}

	public function Setup()
	{
		$Structure = Gdn::Structure();

		// Add a column to the category table.
		$Structure->Table('Category')
			->Column('ShowInAllDiscussions', 'tinyint(4)', '1')
			->Set(FALSE, FALSE);
	}

}