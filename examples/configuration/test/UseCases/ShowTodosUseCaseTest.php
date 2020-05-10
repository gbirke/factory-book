<?php

declare( strict_types = 1 );

namespace App\Test\UseCases;

use org\bovigo\vfs\vfsStream;
// TODO use PHPUnit TestCase 
// TODO use all references classes

class ShowTodosUseCaseTest extends TestCase {

	protected function setUp(): void {
		$root = vfsStream::setup();
		$this->persistencFile = vfsStream::newFile( 'todos.json' )
			->at( $root );
		$this->factory = new UseCaseFactory(
			new PersistenceFactory( $this->persistenceFile->url() ),
			new ViewFactory()
		);
	}

	public function testInitializedTodoFileItRendersTodos() {
		$this->persistenceFile->setContent( json_encode( [
			['name' => 'First', 'done' => true],
			['name' => 'Second', 'done' => false]
		] ) );
		$useCase = $this->factory->getShowTodosUseCase();

		ob_start();
		$useCase->execute();
		$result = ob_end_clean();

		$this->assertStringContainsString( 'First', $result );
		$this->assertStringContainsString( 'Second', $result );
		$this->assertStringContainsString( '[ ]', $result );
		$this->assertStringContainsString( '[X]', $result );
	}
}

