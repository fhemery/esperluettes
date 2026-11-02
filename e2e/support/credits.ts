import { artisan } from './sail';
import { ACCOUNTS } from './fixtures';

/** Sets the seeded author's chapter-credit balance, straight in the e2e database. */
export function setAuthorCredits(balance: number): void {
  const email = ACCOUNTS.author.email;
  const code =
    `$id = \\DB::table("users")->where("email", "${email}")->value("id");` +
    `\\DB::table("story_chapter_credits")->where("user_id", $id)->update(["credits_gained" => ${balance}, "credits_spent" => 0]);`;
  const res = artisan(['tinker', `--execute=${code}`]);
  if (!res.ok) throw new Error(`setAuthorCredits failed: ${res.output}`);
}
