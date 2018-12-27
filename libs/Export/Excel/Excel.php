<?php
define('EXCEL_LOADER_PATH', _XE_PATH_ . '/modules/loginlog/libs/PHPExcel.php');

class Export_Excel extends Export_Core
{
	private $filename;
	private $xlsDoc;
	private $options;

	/**
	 * Constructor
	 * @access public
	 * @param array $options
	 * @return void
	 */
	public function __construct(Array $options = array())
	{
		if(!class_exists('PHP_EXCEL'))
		{
			if(is_readable(EXCEL_LOADER_PATH))
			{
				require EXCEL_LOADER_PATH;
			}
			else
			{
				throw new Exception('Error : ' . EXCEL_LOADER_PATH . ' file not found.');
			}
		}

		$this->xlsDoc = new PHPExcel();

		$this->setOptions($options);
	}

	/**
	 * Set export option.
	 * @access public
	 * @param array $option
	 * @return bool
	 */
	public function setOptions(Array $option = array())
	{
		// If option is not array, return false
		if(!is_array($option))
		{
			return FALSE;
		}

		// There is no option items, return false
		if(count($option) < 1)
		{
			return FALSE;
		}

		$xlsDoc = $this->xlsDoc;

		if(isset($option['start_date']))
		{
			$this->start_date = $option['start_date'];
		}

		if(isset($option['end_date']))
		{
			$this->end_date = $option['end_date'];
		}

		if(isset($option['title']))
		{
			$this->title = $option['title'];
		}

		// Set filename
		if(isset($option['filename']))
		{
			$option['filename'] = trim($option['filename']);
			if(isset($option['filename']{0}))
			{
				$this->filename = $option['filename'] . '.xls';
			}
		}

		// Set default font style
		if(isset($option['font']))
		{
			// Set default font name
			if(isset($option['font']['name']) && $option['font']['name'])
			{
				$xlsDoc->getDefaultStyle()->getFont()->setName($option['font']['name']);
			}

			// Set default font size
			if(isset($option['font']['size']) && $option['font']['size'])
			{
				$xlsDoc->getDefaultStyle()->getFont()->setSize($option['font']['size']);
			}
		}

		// Set document properties
		if(isset($option['properties']))
		{
			if(isset($option['properties']['creator']))
			{
				$xlsDoc->getProperties()->setCreator($option['properties']['creator']);
			}

			if(isset($option['properties']['modifier']))
			{
				$xlsDoc->getProperties()->setLastModifiedBy($option['properties']['modifier']);
			}
		}

		return TRUE;
	}

	/**
	 * Create a new worksheet.
	 * @param bool $active
	 * @return void
	 */
	public function createSheet($active = FALSE)
	{
		$this->xlsDoc->createSheet();

		/**
		 * If $active variable is set TRUE, active immediately
		 */
		if($active === TRUE)
		{
			$this->xlsDoc->setActiveSheetIndex($this->xlsDoc->getSheetCount() - 1);
		}
	}

	/**
	 * 파일 다운로드
	 */
	public function export()
	{
		if(!$this->filename)
		{
			$msg = Context::getLang('msg_loginlg_filename_required');
			throw new Exception($msg);
			return FALSE;
		}

		// loginlogModel 객체 생성
		$oLoginlogModel = getModel('loginlog');

		// 로그인 기록 모듈의 설정값을 가져옵니다
		$config = $oLoginlogModel->getModuleConfig();

		$startDate = str_replace('-', '', $this->start_date);
		$endDate = str_replace('-', '', $this->end_date);
		$startPage = Context::get('startPage') ? Context::get('startPage') : 1;
		$listCount = $config->exportConfig->listCount ? $config->exportConfig->listCount : (int)Context::get('listCount');
		$pageCount = $config->exportConfig->pageCount ? $config->exportConfig->pageCount : Context::get('pageCount');
		$includeAdmin = ($config->exportConfig->includeAdmin ? $config->exportConfig->includeAdmin : Context::get('includeAdmin')) == 'Y';
		$isSucceed = Context::get('isSucceed');
		$ipaddress = Context::get('ipaddress');

		if(!$listCount) $listCount = 100;
		if(!$pageCount) $pageCount = 10;

		// 기본 Query ID 지정
		$query_id = 'loginlog.getLoginlogListWithinMember';

		// $args 변수 초기화
		$args = new stdClass;

		$selected_log_srls = Context::get('cart');
		if($selected_log_srls)
		{
			$args->s_log_srl = $selected_log_srls;
		}
		else
		{
			// 추출 대상에 따라 Query ID 변경
			switch($config->exportConfig->exportType)
			{
				case 'include':
					if(is_array($config->exportConfig->includeGroup) && count($config->exportConfig->includeGroup) > 0)
					{
						$args->include_group_srls = implode(',', $config->exportConfig->includeGroup);
						$query_id = 'loginlog.getLoginlogListWithinMemberGroup';
					}
					break;
				case 'exclude':
					if(is_array($config->exportConfig->excludeGroup) && count($config->exportConfig->excludeGroup) > 0)
					{
						$args->exclude_group_srls = implode(',', $config->exportConfig->excludeGroup);
						$query_id = 'loginlog.getLoginlogListWithinMemberGroup';
					}
					break;
			}
		}

		$args->daterange_start = $startDate;
		$args->daterange_end = $endDate;

		$args->list_count = $listCount; ///< 한페이지에 보여줄 기록 수
		$args->page_count = $pageCount; ///< 페이지 네비게이션에 나타날 페이지의 수
		$args->sort_index = 'loginlog.regdate';
		$args->order_type = 'desc';
		if(!$includeAdmin) $args->is_admin = 'N';

		if($exportFileType == 'html') $args->page_count = 1;

		// DB에서 불러올 column 목록을 미리 정의해놓음
		$columnList = array(
			'member.user_id', 'member.user_name', 'member.nick_name',
			'loginlog.regdate', 'loginlog.ipaddress', 'loginlog.is_succeed', 'loginlog.platform', 'loginlog.browser'
		);

		$type = Context::get('type');

		$curPage = $startPage; ///< 처음 보여줄 페이지 값
		do {
			$args->page = $curPage; ///< 페이지

			// 데이터 출력
			$output = executeQueryArray($query_id, $args, $columnList);

			// 시트 생성
			if($curPage > 1)
			{
				$this->createSheet(TRUE);
			}

			// activeSheet()에 대한 alias
			$sheetObj = $this->xlsDoc->getActiveSheet();

			// 기본 높이 지정
			$sheetObj->getDefaultRowDimension()->setRowHeight(25);

			// 2행의 높이를 40으로
			$sheetObj->getRowDimension(2)->setRowHeight(40);

			// 3행의 높이를 3으로
			$sheetObj->getRowDimension(3)->setRowHeight(3);

			// 5행의 높이를 3으로
			$sheetObj->getRowDimension(5)->setRowHeight(3);

			// sheet 제목 지정
			$sheetObj->setTitle('Page '.$curPage);

			// header 항목 표시
			$sheetObj->setCellValue('B1', $this->title);
			$sheetObj->setCellValue('B4', '번호');
			$sheetObj->setCellValue('C6', '분류');
			$sheetObj->setCellValue('D6', '이름');
			$sheetObj->setCellValue('E6', '아이디');
			$sheetObj->setCellValue('F6', '닉네임');
			$sheetObj->setCellValue('G6', 'OS');
			$sheetObj->setCellValue('H6', '브라우저');
			$sheetObj->setCellValue('I6', 'IP 주소');
			$sheetObj->setCellValue('J6', '로그인 시간');

			$sheetObj->setCellValue('I4', '출력일자 :');
			$sheetObj->setCellValue('J4', zdate(date('YmdHis')));

			// 제목 굵기 및 정렬
			$titleStyle = $sheetObj->getStyle('B1');

			// 타이틀 글씨 크기
			$titleStyle->getFont()->setName('나눔고딕')->setSize(20)->setBold(true);
			$titleStyle->getFont()->getColor()->setARGB('FF333399');
			$titleStyle->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
			$titleStyle->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

			// 배경색 및 글꼴, 글씨 크기
			$sheetObj->getStyle('B6:J6')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
			$sheetObj->getStyle('B6:J6')->getFill()->getStartColor()->setARGB('FFFFFF99');
			$sheetObj->getStyle('B6:J6')->getFont()->setName('나눔고딕');
			$sheetObj->getStyle('B6:J6')->getFont()->setSize(9);
			$sheetObj->getStyle('B6:J6')->getFont()->setBold(true);
			$sheetObj->getStyle('I4:J4')->getFont()->setName('나눔고딕');
			$sheetObj->getStyle('I4')->getFont()->setBold(true);
			// 항목 이름 정렬
			$sheetObj->getStyle('G4:J4')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			$sheetObj->getStyle('B6:J6')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

			// 로그인 기록이 있으면 loop를 돌면서 데이터를 출력함
			if(count($output->data))
			{
				$row = 7;
				foreach($output->data as $key => $val)
				{
					if($val->is_succeed == 'Y')
					{
						$sheetObj->getStyle('C'.$row)->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_DARKGREEN);
					}
					elseif($val->is_succeed == 'N')
					{
						$sheetObj->getStyle('C'.$row)->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);
						$sheetObj->getStyle('C'.$row)->getFont()->setBold(true);
					}
					$sheetObj->getStyle('C'.$row)->getFont()->setBold(true);
					$sheetObj->setCellValue('B'.$row, $key);
					$sheetObj->setCellValue('C'.$row, $val->is_succeed == 'Y' ? '성공' : '실패');
					$sheetObj->setCellValue('D'.$row, $val->user_name);
					$sheetObj->setCellValue('E'.$row, $val->user_id);
					$sheetObj->setCellValue('F'.$row, $val->nick_name);
					$sheetObj->setCellValue('G'.$row, $val->platform);
					$sheetObj->setCellValue('H'.$row, $val->browser);
					$sheetObj->setCellValue('I'.$row, $val->ipaddress);
					$sheetObj->setCellValue('J'.$row, zdate($val->regdate));
					$row++;
				}

				$sheetObj->getStyle('B6:J'.($row-1))->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			}
			// 열 너비를 자동으로 지정
			$sheetObj->getColumnDimension('B')->setWidth(8);
			$sheetObj->getColumnDimension('C')->setWidth(8);
			$sheetObj->getColumnDimension('D')->setAutoSize(true);
			$sheetObj->getColumnDimension('E')->setAutoSize(true);
			$sheetObj->getColumnDimension('F')->setAutoSize(true);
			$sheetObj->getColumnDimension('G')->setAutoSize(true);
			$sheetObj->getColumnDimension('H')->setAutoSize(true);
			$sheetObj->getColumnDimension('I')->setAutoSize(true);
			$sheetObj->getColumnDimension('J')->setAutoSize(true);

			// 열 너비가 너무 좁게 지정되는 경우가 있어 수동으로 너비를 늘려줍니다

			if(count($output->data) < 1 || true)
			{
				$D_size = $sheetObj->getColumnDimension('D')->getWidth();
				$E_size = $sheetObj->getColumnDimension('E')->getWidth();
				$F_size = $sheetObj->getColumnDimension('F')->getWidth();
				$G_size = $sheetObj->getColumnDimension('G')->getWidth();
				$H_size = $sheetObj->getColumnDimension('H')->getWidth();
				$I_size = $sheetObj->getColumnDimension('I')->getWidth();
				$J_size = $sheetObj->getColumnDimension('J')->getWidth();
				$sheetObj->getColumnDimension('D')->setAutoSize(false);
				$sheetObj->getColumnDimension('E')->setAutoSize(false);
				$sheetObj->getColumnDimension('F')->setAutoSize(false);
				$sheetObj->getColumnDimension('G')->setAutoSize(false);
				$sheetObj->getColumnDimension('H')->setAutoSize(false);
				$sheetObj->getColumnDimension('I')->setAutoSize(false);
				$sheetObj->getColumnDimension('J')->setAutoSize(false);
				$sheetObj->getColumnDimension('D')->setWidth($D_size + 20);
				$sheetObj->getColumnDimension('E')->setWidth($E_size + 20);
				$sheetObj->getColumnDimension('F')->setWidth($F_size + 20);
				$sheetObj->getColumnDimension('G')->setWidth($G_size + 20);
				$sheetObj->getColumnDimension('H')->setWidth($H_size + 20);
				$sheetObj->getColumnDimension('I')->setWidth($I_size + 20);
				$sheetObj->getColumnDimension('J')->setWidth($J_size + 20);

			}

			// 제목 셀 병합
			$sheetObj->mergeCells('B1:J2');

			// 전체 데이터에 테두리를 지정합니다
			$styleArray = array(
				'borders' => array(
					'allborders' => array(
						'style' => PHPExcel_Style_Border::BORDER_THIN,
						'color' => array('argb' => 'FF555555'),
					),
				),
			);

			// Row 변수값이 없는 경우 기본값을 지정해서 에러가 나지 않도록 합니다.
			if(!$row) $row = 7;
			//$sheetObj->getStyle('B6:J'.($row-1))->applyFromArray($styleArray);

			/**
			 * [자동 필터 활성화]
			 * 자동 필터 기능이 설치되지 않은 PC에서 에러가 발생할 우려가 있으므로 주석 처리함
			* $sheetObj->setAutoFilter('C6:J'.$row);
			*/
			$sheetObj->setAutoFilter('B6:J'.$row);
			$curPage++;
			$output->page_navigation->getNextPage();
		} while($output->page_navigation->cur_page < $output->page_navigation->total_page);

		$this->xlsDoc->setActiveSheetIndex(0);

		header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
		header('Content-Disposition: attachment;filename="' . $this->filename . '"');
		header('Cache-Control: max-age=0');

		// Excel5 포맷으로 저장 엑셀 2007 포맷으로 저장하고 싶은 경우 'Excel2007'로 변경합니다.
		$objWriter = PHPExcel_IOFactory::createWriter($this->xlsDoc, 'Excel5'); 

		// 서버에 파일을 쓰지 않고 바로 다운로드 받습니다.
		$objWriter->save('php://output');
		Context::close();
	}
}