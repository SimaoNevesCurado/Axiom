<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Concerns;

use Laravel\Prompts\ConfirmPrompt;
use Laravel\Prompts\MultiSelectPrompt;
use Laravel\Prompts\PasswordPrompt;
use Laravel\Prompts\Prompt;
use Laravel\Prompts\SelectPrompt;
use Laravel\Prompts\SuggestPrompt;
use Laravel\Prompts\TextPrompt;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

trait ConfiguresPrompts
{
    protected function configurePromptFallbacks(InputInterface $input, OutputInterface $output): void
    {
        Prompt::fallbackWhen(! $input->isInteractive() || PHP_OS_FAMILY === 'Windows');

        TextPrompt::fallbackUsing(fn (TextPrompt $prompt): string => $this->promptWithFallbackUntilValid(
            fn (): string => (new SymfonyStyle($input, $output))->ask($prompt->label, $prompt->default ?: null) ?? '',
            $prompt->required,
            $prompt->validate,
            $output,
        ));

        PasswordPrompt::fallbackUsing(fn (PasswordPrompt $prompt): string => $this->promptWithFallbackUntilValid(
            fn (): string => (new SymfonyStyle($input, $output))->askHidden($prompt->label) ?? '',
            $prompt->required,
            $prompt->validate,
            $output,
        ));

        ConfirmPrompt::fallbackUsing(fn (ConfirmPrompt $prompt): bool => $this->promptWithFallbackUntilValid(
            fn (): bool => (new SymfonyStyle($input, $output))->confirm($prompt->label, $prompt->default),
            $prompt->required,
            $prompt->validate,
            $output,
        ));

        SelectPrompt::fallbackUsing(fn (SelectPrompt $prompt): int|string => $this->promptWithFallbackUntilValid(
            fn (): mixed => (new SymfonyStyle($input, $output))->choice($prompt->label, $prompt->options, $prompt->default),
            false,
            $prompt->validate,
            $output,
        ));

        MultiSelectPrompt::fallbackUsing(function (MultiSelectPrompt $prompt) use ($input, $output): array {
            if ($prompt->default !== []) {
                return $this->promptWithFallbackUntilValid(
                    fn (): array => (new SymfonyStyle($input, $output))->choice($prompt->label, $prompt->options, implode(',', $prompt->default), true),
                    $prompt->required,
                    $prompt->validate,
                    $output,
                );
            }

            return $this->promptWithFallbackUntilValid(
                fn (): array => collect((new SymfonyStyle($input, $output))->choice(
                    $prompt->label,
                    array_is_list($prompt->options)
                        ? ['None', ...$prompt->options]
                        : ['none' => 'None', ...$prompt->options],
                    'None',
                    true,
                ))->reject(array_is_list($prompt->options) ? 'None' : 'none')->all(),
                $prompt->required,
                $prompt->validate,
                $output,
            );
        });

        SuggestPrompt::fallbackUsing(fn (SuggestPrompt $prompt): mixed => $this->promptWithFallbackUntilValid(
            function () use ($prompt, $input, $output): mixed {
                $question = new Question($prompt->label, $prompt->default);

                is_callable($prompt->options)
                    ? $question->setAutocompleterCallback($prompt->options)
                    : $question->setAutocompleterValues($prompt->options);

                return (new SymfonyStyle($input, $output))->askQuestion($question);
            },
            $prompt->required,
            $prompt->validate,
            $output,
        ));
    }

    /**
     * @template T
     *
     * @param  callable(): T  $prompt
     * @return T
     */
    protected function promptWithFallbackUntilValid(callable $prompt, bool|string $required, ?callable $validate, OutputInterface $output): mixed
    {
        while (true) {
            $result = $prompt();

            if ($required && ($result === '' || $result === [] || $result === false)) {
                $output->writeln('<error>'.(is_string($required) ? $required : 'Required.').'</error>');

                continue;
            }

            if ($validate !== null) {
                $error = $validate($result);

                if (is_string($error) && $error !== '') {
                    $output->writeln("<error>{$error}</error>");

                    continue;
                }
            }

            return $result;
        }
    }
}
