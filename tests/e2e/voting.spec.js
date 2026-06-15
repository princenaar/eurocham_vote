import { expect, test } from '@playwright/test';
import { spawnSync } from 'node:child_process';
import { e2eEnv, repoRoot } from './support/e2e-env.mjs';

const QUESTION_TYPE = 'Questions Oui / Non / Abstention';
const BOARD_TYPE = 'Élection du Conseil d’Administration';

test.describe.configure({ mode: 'serial' });

test.beforeEach(() => {
    const result = spawnSync('php', ['artisan', 'e2e:prepare'], {
        cwd: repoRoot,
        env: e2eEnv(),
        stdio: 'inherit',
    });

    expect(result.status).toBe(0);
});

test('vote 1 questionnaire extraordinaire: R1 and R2 through public flow and results', async ({ page }) => {
    await adminLogin(page);
    await openElection(page, 'Vote 1 — Assemblée générale à titre extraordinaire', QUESTION_TYPE);

    await castQuestionsVote(page, {
        company: '2S CONSULTING',
        answers: ['yes', 'no'],
        expectedQuestions: [
            'Première résolution — Adoption des modifications des statuts',
            'Deuxième résolution — Pouvoirs pour formalités',
        ],
    });

    await closeElection(page, 'Vote 1 — Assemblée générale à titre extraordinaire', QUESTION_TYPE);

    await page.goto('/resultats');
    await expect(page.getByTestId('questions-results')).toBeVisible();
    await expect(page.getByTestId('question-result')).toHaveCount(2);
    await expect(page.getByText('Résultat : Oui')).toBeVisible();
    await expect(page.getByText('Résultat : Non')).toBeVisible();
});

test('vote 2 questionnaire ordinaire: R3 to R7 with mixed answers', async ({ page }) => {
    await adminLogin(page);
    await openElection(page, 'Vote 2 — Assemblée générale à titre ordinaire', QUESTION_TYPE);

    await castQuestionsVote(page, {
        company: 'AFRICA MOVE',
        answers: ['yes', 'yes', 'no', 'abstain', 'yes'],
        expectedQuestions: [
            'Troisième résolution — Approbation du rapport moral',
            'Quatrième résolution — Approbation du rapport financier et des états financiers',
            'Cinquième résolution — Quitus au Conseil d’Administration',
            'Sixième résolution — Adoption du budget prévisionnel 2026',
            'Septième résolution — Nomination du Commissaire aux comptes',
        ],
    });

    await closeElection(page, 'Vote 2 — Assemblée générale à titre ordinaire', QUESTION_TYPE);

    await page.goto('/resultats');
    await expect(page.getByTestId('questions-results')).toBeVisible();
    await expect(page.getByTestId('question-result')).toHaveCount(5);
    await expect(page.getByText('Abstention').first()).toBeVisible();
    await expect(page.getByText('Résultat : Aucun suffrage exprimé')).toBeVisible();
});

test('vote 4 questionnaire: R9 single resolution', async ({ page }) => {
    await adminLogin(page);
    await openElection(page, 'Vote 4 — Pouvoirs pour l’exécution des délibérations', QUESTION_TYPE);

    await castQuestionsVote(page, {
        company: 'AFRICA WORKS',
        answers: ['yes'],
        expectedQuestions: [
            'Neuvième résolution — Pouvoirs pour l’exécution des délibérations',
        ],
    });

    await closeElection(page, 'Vote 4 — Pouvoirs pour l’exécution des délibérations', QUESTION_TYPE);

    await page.goto('/resultats');
    await expect(page.getByTestId('questions-results')).toBeVisible();
    await expect(page.getByTestId('question-result')).toHaveCount(1);
    await expect(page.getByText('Résultat : Oui')).toBeVisible();
});

test('CA Mode A: 21 candidates, 5 to 20 selections, avatars, confirmation and duplicate block', async ({ page }) => {
    await adminLogin(page);
    await openElection(page, 'Vote 3 — Renouvellement du Conseil d’Administration', BOARD_TYPE);

    await page.goto('/vote');
    await identifyCompany(page, 'AGL GROUP');
    await expect(page.getByTestId('board-ballot')).toBeVisible();
    await expect(page.getByTestId('candidate-order-note')).toContainText('ordre d’inscription');
    await expect(page.getByText('entre 5 et 20')).toBeVisible();
    await expect(page.getByTestId('candidate-checkbox')).toHaveCount(21);
    await expect(page.getByTestId('candidate-avatar')).toHaveCount(21);
    await expectCandidateImagesLoaded(page);
    await expect(page.getByTestId('board-review-submit')).toBeDisabled();

    const candidates = page.getByTestId('candidate-checkbox');
    for (let index = 0; index < 4; index += 1) {
        await candidates.nth(index).check();
    }

    await expect(page.getByTestId('board-review-submit')).toBeDisabled();
    await candidates.nth(4).check();
    await expect(page.getByTestId('board-review-submit')).toBeEnabled();

    for (let index = 5; index < 20; index += 1) {
        await candidates.nth(index).check();
    }

    await expect(page.getByTestId('selection-counter')).toContainText('20 / 20');
    await expect(candidates.nth(20)).toBeDisabled();
    await expect(page.getByTestId('board-review-submit')).toBeEnabled();
    await page.getByTestId('board-review-submit').click();

    await expect(page.getByTestId('board-review')).toBeVisible();
    await expect(page.getByTestId('selected-candidate')).toHaveCount(20);
    await page.getByTestId('confirm-vote-submit').click();

    await expect(page.getByTestId('vote-confirmation')).toBeVisible();
    await expect(page.getByTestId('vote-reference')).toContainText(/EC2026-/);

    await page.goto('/vote');
    await identifyCompany(page, 'AGL GROUP', { expectRedirect: false });
    await expect(page.getByText('a déjà voté').first()).toBeVisible();
});

test('CA Mode B: automatic election with no ballot', async ({ page }) => {
    await adminLogin(page);
    await openElection(page, 'E2E — Conseil d’Administration Mode B', BOARD_TYPE);

    await page.goto('/vote');
    await expect(page.getByTestId('board-auto')).toBeVisible();
    await expect(page.getByText('Élection automatique')).toBeVisible();
    await expect(page.getByTestId('candidate-avatar')).toHaveCount(5);
    await expectCandidateImagesLoaded(page);

    await page.goto('/vote/bulletin');
    await expect(page.getByTestId('board-auto')).toBeVisible();
    await expect(page.getByTestId('board-ballot')).toHaveCount(0);
});

test('global activation: second vote cannot be opened while another vote is active', async ({ page }) => {
    await adminLogin(page);
    await openElection(page, 'Vote 1 — Assemblée générale à titre extraordinaire', QUESTION_TYPE);

    await selectAdminElection(page, 'Vote 2 — Assemblée générale à titre ordinaire', QUESTION_TYPE);
    await expect(page.getByTestId('admin-window-toggle')).toBeDisabled();
});

async function adminLogin(page) {
    await page.goto('/admin/login');
    await page.getByTestId('admin-email').fill('admin@eurocham.sn');
    await page.getByTestId('admin-password').fill('eurocham2026');
    await page.getByTestId('admin-login-submit').click();
    await expect(page).toHaveURL(/\/admin$/);
}

async function selectAdminElection(page, name, typeLabel) {
    await page.goto('/admin/election');
    const switcher = page.getByTestId('admin-election-switcher');

    if (await switcher.count()) {
        await switcher.selectOption({ label: `${name} — ${typeLabel}` });
    }

    await expect(page.getByTestId('admin-current-election')).toHaveText(name);
}

async function openElection(page, name, typeLabel) {
    await selectAdminElection(page, name, typeLabel);
    await expect(page.getByTestId('admin-window-toggle')).toContainText('Ouvrir le vote');
    await page.getByTestId('admin-window-toggle').click();
    await expect(page.getByText('Le vote est OUVERT.')).toBeVisible();
}

async function closeElection(page, name, typeLabel) {
    await selectAdminElection(page, name, typeLabel);
    await expect(page.getByTestId('admin-window-toggle')).toContainText('Clôturer le vote');
    await page.getByTestId('admin-window-toggle').click();
    await expect(page.getByText('Le vote est CLÔTURÉ.')).toBeVisible();
}

async function identifyCompany(page, company, options = {}) {
    const { expectRedirect = true } = options;

    await page.getByTestId('company-search').fill(company);
    await page.getByTestId('company-option').filter({ hasText: company }).first().click();
    await page.getByTestId('representative-last-name').fill('Diop');
    await page.getByTestId('representative-first-name').fill('Awa');
    await page.getByTestId('identify-submit').click();

    if (expectRedirect) {
        await expect(page).toHaveURL(/\/vote\/bulletin$/);
    }
}

async function castQuestionsVote(page, { company, answers, expectedQuestions }) {
    await page.goto('/vote');
    await identifyCompany(page, company);
    await expect(page.getByTestId('questions-ballot')).toBeVisible();
    await expect(page.getByTestId('question-fieldset')).toHaveCount(expectedQuestions.length);

    for (const title of expectedQuestions) {
        await expect(page.getByText(title)).toBeVisible();
    }

    const questions = page.getByTestId('question-fieldset');
    for (const [index, answer] of answers.entries()) {
        await questions.nth(index).getByTestId(`question-answer-${answer}`).check();
    }

    await page.getByTestId('questions-review-submit').click();
    await expect(page.getByTestId('questions-review')).toBeVisible();
    await expect(page.getByTestId('reviewed-question')).toHaveCount(expectedQuestions.length);
    await page.getByTestId('confirm-vote-submit').click();

    await expect(page.getByTestId('vote-confirmation')).toBeVisible();
    await expect(page.getByTestId('vote-reference')).toContainText(/EC2026-/);
}

async function expectCandidateImagesLoaded(page) {
    await expect.poll(async () => {
        return page.getByTestId('candidate-avatar').evaluateAll((images) => {
            return images.every((image) => image.complete && image.naturalWidth > 0);
        });
    }).toBe(true);
}
