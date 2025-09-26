/**
 * Audio Helper - простое управление воспроизведением аудио
 * Обеспечивает воспроизведение только одного аудио файла одновременно
 */

class AudioHelper {
    private static instance: AudioHelper;
    private currentAudio: HTMLAudioElement | null = null;

    private constructor() {
        this.init();
    }

    public static getInstance(): AudioHelper {
        if (!AudioHelper.instance) {
            AudioHelper.instance = new AudioHelper();
        }
        return AudioHelper.instance;
    }

    private init(): void {
        // Слушаем событие play на всех audio элементах
        document.addEventListener('play', (event) => {
            const audio = event.target as HTMLAudioElement;
            if (audio.tagName === 'AUDIO') {
                this.handleAudioPlay(audio);
            }
        }, true);
    }

    /**
     * Обрабатывает воспроизведение аудио
     */
    private handleAudioPlay(audio: HTMLAudioElement): void {
        // Если это не тот же аудио файл, что уже играет
        if (this.currentAudio !== audio) {
            // Останавливаем текущий аудио файл
            this.stopCurrentAudio();
            
            // Устанавливаем новый как текущий
            this.currentAudio = audio;
            
            // Добавляем обработчик окончания
            audio.addEventListener('ended', () => {
                this.currentAudio = null;
            });
        }
    }

    /**
     * Останавливает текущий аудио файл
     */
    private stopCurrentAudio(): void {
        if (this.currentAudio && !this.currentAudio.paused) {
            this.currentAudio.pause();
        }
    }

    /**
     * Останавливает все аудио файлы
     */
    public stopAll(): void {
        this.stopCurrentAudio();
        this.currentAudio = null;
    }
}

// Инициализируем AudioHelper при загрузке DOM
document.addEventListener('DOMContentLoaded', () => {
    AudioHelper.getInstance();
});

// Экспортируем для использования в других модулях
export default AudioHelper;
