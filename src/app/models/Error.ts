export class ErrorResponse {
	constructor(
		public error: Error) {
	}
}

export class Error {
	constructor(
		public code: number,
		public codeInWords: string,
		public message: string) {
	}
}