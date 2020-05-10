<?php
declare( strict_types = 1 );

namespace App\Infrastructure;

class PersistenceFactory {
	private TodoRepository $todoRepo;
	private FileReader $fileReader;
	private string $persistenceFile;

	public function getJsonTodoRepository(): TodoRepository {
		if ( $this->todoRepo === null ) {
			$this->todoRepo = new JsonTodoRepository( 
				$this->getFileReader()
			);
		}
		return $this->todoRepo;
	}

	public function getFileReader(): FileReader {
		if ( $this->fileReader === null ) {
			$this->fileReader = new SimpleFileReader(
				$this->persistenceFile
			);
		}
		return $this->fileReader;
	}
}

