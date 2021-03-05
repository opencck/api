<?php
namespace APP;

use PHPUnit\Framework\TestCase;
use Exception;

/**
 * Class ModelTest
 * @package APP
 */
class ModelTest extends TestCase {
	/**
	 * Recursive structural grouping of elements
	 * @dataProvider dataProvider
	 * @param $items array
	 * @param $groups array
	 * @param $getAlias string
	 * @param $expected array
	 * @throws Exception
	 */
	public function testGroup($items, $groups, $getAlias, $expected) {
		$model = new Model();
		$this->assertEquals($expected, $model->group($items, $groups, $getAlias));
	}
	public function dataProvider() {
		// prettier-ignore
		return [
			'simplify' => [
				[ // items
					['a.id' => 1, 'a.name' => 'value']
				],[ // groups
					'a' => ['name' => 'test', 'key' => 'id']
				], 'a', // alias
				[ // expected
					['id' => 1, 'name' => 'value']
				],
			],
			'difference one' => [
				[ // items
					['a.id' => 1, 'b.id' => 1],
					['a.id' => 1, 'b.id' => 2]
				],
				[ // groups
					'a' => [
						'name' => 'one',
						'key' => 'id',
					],
					'b' => [
						'name' => 'two',
						'key' => 'id',
					]
				], 'a', // alias
				[ // expected
					['id' => 1]
				],
			],
			'difference two' => [
				[ // items
					['a.id' => 1, 'b.id' => 1],
					['a.id' => 1, 'b.id' => 2]
				],
				[ // groups
					'a' => [
						'name' => 'one',
						'key' => 'id',
					],
					'b' => [
						'name' => 'two',
						'key' => 'id',
					]
				], 'b', // alias
				[ // expected
					['id' => 1],
					['id' => 2]
				],
			],
			'recursiveRelation' => [
				[ // items
					['a.id' => 1, 'b.id' => 2, 'b.one_id' => 1],
					['a.id' => 1, 'b.id' => 3, 'b.one_id' => 1]
				],
				[ // groups
					'b' => [
						'name' => 'two',
						'key' => 'id',
						'relation'=>[
							'a' => [
								'name' => 'one',
								'key' => 'id'
							]
						]
					]
				], 'a', // alias
				[ // expected
					[
						'id' => 1,
						'two'=>[
							['id' => 2, 'one_id' => 1],
							['id' => 3, 'one_id' => 1]
						]
					]
				],
			],
			'complexRelation' => [
				[ // items
					['a.id' => 1, 'b.id' => 2, 'b.one_id' => 1, 'c.id' => 4, 'c.two_id' => 2],
					['a.id' => 1, 'b.id' => 2, 'b.one_id' => 1, 'c.id' => 5, 'c.two_id' => 2],
					['a.id' => 1, 'b.id' => 3, 'b.one_id' => 1, 'c.id' => 6, 'c.two_id' => 3],
					['a.id' => 1, 'b.id' => 3, 'b.one_id' => 1, 'c.id' => 7, 'c.two_id' => 3],
				],
				[ // groups
					'c' => [
						'name' => 'three',
						'key' => 'id',
						'relation'=>[
							'b' => [
								'name' => 'two',
								'key' => 'id'
							]
						]
					],
					'b' => [
						'name' => 'two',
						'key' => 'id',
						'relation'=>[
							'a' => [
								'name' => 'one',
								'key' => 'id'
							]
						]
					],
				], 'a', // alias
				[ // expected
					[
						'id' => 1,
						'two' => [
							[
								'id' => 2,
								'one_id' => 1,
								'three' => [
									['id' => 4, 'two_id' => 2],
									['id' => 5, 'two_id' => 2]
								],
							],
							[
								'id' => 3,
								'one_id' => 1,
								'three' => [
									['id' => 6, 'two_id' => 3],
									['id' => 7, 'two_id' => 3]
								]
							]
						]
					]
				],
			],
			'complexRecursiveRelation' => [
				[ // items
					[
						'a.id' => 1,
						'b.id' => 2, 	'b.one_id' => 1,
						'c.id' => 4, 						'c.two_id' => 2,
						'd.id' => 8, 	'd.one_id' => 1, 	'd.two_id' => 2,
						'e.id' => 12, 											'e.four_id' => 8,
					],
					[
						'a.id' => 2,
						'b.id' => 3, 	'b.one_id' => 2,
						'c.id' => 5, 						'c.two_id' => 3,
						'd.id' => 9, 	'd.one_id' => 2, 	'd.two_id' => 3,
						'e.id' => 13, 											'e.four_id' => 9,
					],
					[
						'a.id' => 2,
						'b.id' => 4, 	'b.one_id' => 2,
						'c.id' => 5, 						'c.two_id' => 4,
						'd.id' => 9, 	'd.one_id' => 2, 	'd.two_id' => 4,
						'e.id' => 14, 											'e.four_id' => 9,
					],
					[
						'a.id' => 1,
						'b.id' => 5, 	'b.one_id' => 1,
						'c.id' => 6, 						'c.two_id' => 5,
						'd.id' => 10, 	'd.one_id' => 1, 	'd.two_id' => 5,
						'e.id' => 15, 											'e.four_id' => 10,
					],
					[
						'a.id' => 2,
						'b.id' => 6, 	'b.one_id' => 2,
						'c.id' => 7, 						'c.two_id' => 6,
						'd.id' => 11, 	'd.one_id' => 2, 	'd.two_id' => 6,
						'e.id' => 16, 											'e.four_id' => 11,
					],
					[
						'a.id' => 2,
						'b.id' => 7, 	'b.one_id' => 2,
						'c.id' => 7, 						'c.two_id' => 7,
						'd.id' => 11, 	'd.one_id' => 2, 	'd.two_id' => 7,
						'e.id' => 17, 											'e.four_id' => 11,
					],
				],
				[ // groups
					'e' => [
						'name' => 'five',
						'key' => 'id',
						'relation' => [
							'd' => [
								'name' => 'four',
								'key' => 'id'
							]
						]
					],
					'd' => [
						'name' => 'four',
						'key' => 'id',
						'relation' => [
							'a' => [
								'name' => 'one',
								'key' => 'id'
							],
							'b' => [
								'name' => 'two',
								'key' => 'id'
							]
						]
					],
					'c' => [
						'name' => 'three',
						'key' => 'id',
						'relation' => [
							'b' => [
								'name' => 'two',
								'key' => 'id',
								'relation' => [
									'a' => [
										'name' => 'one',
										'key' => 'id',
									]
								]
							]
						]
					],
				], 'a', // alias
				[ // expected
					[
						'id' => 1,
						'two' => [
							[
								'id' => 2,
								'one_id' => 1,
								'three' => [
									['id' => 4, 'two_id' => 2],
								],
								'four' => [
									[
										'id' => 8,
										'two_id' => 2,
										'one_id' => 1,
										'five' => [
											['id' => 12, 'four_id' => 8],
										]
									]
								]
							],
							[
								'id' => 5,
								'one_id' => 1,
								'three' => [
									['id' => 6, 'two_id' => 5],
								],
								'four' => [
									[
										'id' => 10,
										'two_id' => 5,
										'one_id' => 1,
										'five' => [
											['id' => 15, 'four_id' => 10]
										]
									]
								]
							]
						],
						'four' => [
							[
								'id' => 8,
								'one_id' => 1,
								'two_id' => 2,
								'five' => [
									['id' => 12, 'four_id' => 8]
								]
							],
							[
								'id' => 10,
								'one_id' => 1,
								'two_id' => 5,
								'five' => [
									['id' => 15, 'four_id' => 10],
								]
							]
						]
					],
					[
						'id' => 2,
						'two' => [
							[
								'id' => 3,
								'one_id' => 2,
								'four' => [],
								'three' => []
							],
							[
								'id' => 4,
								'one_id' => 2,
								'three' => [
									['id' => 5, 'two_id' => 4],
								],
								'four' => [
									[
										'id' => 9,
										'two_id' => 4,
										'one_id' => 2,
										'five' => [
											['id' => 13, 'four_id' => 9],
											['id' => 14, 'four_id' => 9]
										]
									]
								]
							],
							[
								'id' => 6,
								'one_id' => 2,
								'four' => [],
								'three' => []
							],
							[
								'id' => 7,
								'one_id' => 2,
								'three' => [
									['id' => 7, 'two_id' => 7],
								],
								'four' => [
									[
										'id' => 11,
										'two_id' => 7,
										'one_id' => 2,
										'five' => [
											['id' => 16, 'four_id' => 11],
											['id' => 17, 'four_id' => 11]
										]
									]
								]]
						],
						'four' => [
							[
								'id' => 9,
								'one_id' => 2,
								'two_id' => 4,
								'five' => [
									['id' => 13, 'four_id' => 9],
									['id' => 14, 'four_id' => 9]
								]
							],
							[
								'id' => 11,
								'one_id' => 2,
								'two_id' => 7,
								'five' => [
									['id' => 16, 'four_id' => 11],
									['id' => 17, 'four_id' => 11]
								]
							]
						]
					]
				],
			],
			'emptyRelation' => [
				[ // items
					[
						'a.id' => 1,
						'b.id' => 1, 	'b.one_id' => 2
					],
				],
				[ // groups
					'b' => [
						'name' => 'two',
						'key' => 'id',
						'relation' => [
							'a' => [
								'name' => 'one',
								'key' => 'id'
							]
						]
					],
				], 'a', // alias
				[ // expected
					[
						'id' => 1,
						'two' => [],
					]
				],
			],
			'relationKey' => [
				[ // items
					[
						'a.id' => 1,
						'b.id' => 2, 	'b.key' => 1
					],
				],
				[ // groups
					'b' => [
						'name' => 'two',
						'key' => 'id',
						'relation' => [
							'a' => [
								'name' => 'one',
								'key' => 'id',
								'relationKey' => 'key'
							]
						]
					],
				], 'a', // alias
				[ // expected
					[
						'id' => 1,
						'two' => [
							['id' => 2, 'key' => 1]
						],
					]
				],
			],
		];
	}
}
