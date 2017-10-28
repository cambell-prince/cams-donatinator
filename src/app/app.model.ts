export class AppModel {

    public payAmount: string;
    
    public payVia: string;

    public phase: string;

    public result: string;

    public resultCode: number;

    public resultMessage: string;

    constructor(
        public currency: string,
        public frequency: string,
        public paySelection: number,
    ) {
        this.payAmount = '';
        this.payVia = '';
        this.reset();
    }

    reset() {
        this.phase = 'donate';
        this.result = 'unknown';
        this.resultCode = 0;
        this.resultMessage = '';
    }

}
