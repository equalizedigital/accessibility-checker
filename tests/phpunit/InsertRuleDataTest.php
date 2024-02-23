<?php
/**
 * Test file for InsertRuleData
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Insert_Rule_Data;

/**
 * Test class for InsertRuleData
 */
class InsertRuleDataTest extends WP_UnitTestCase {

	/**
	 * Tests the insert method would return expected data types.
	 */
	public function testRuleInserterReturnLogic() {
		$post     = $this->factory()->post->create_and_get();
		$rule     = 'rule';
		$ruletype = 'ruletype';
		$rule_obj = 'rule_obj';

		$rule_inserter = new Insert_Rule_Data();

		// first call should insert and return null.
		$new_data = $rule_inserter->insert( $post, $rule, $ruletype, $rule_obj );
		$this->assertEquals( null, $new_data );
		// second call is a duplicate and should return the row id.
		$duplicate_data = $rule_inserter->insert( $post, $rule, $ruletype, $rule_obj );
		$this->assertisInt( $duplicate_data );

		// third call should throw an exception because of missing parameters.
		$this->expectException( TypeError::class );
		$rule_inserter->insert();
	}
}
