import axios from "axios";
import type { ValidationErrors } from "@/types/api";

const MONTHS = [
  "Jan",
  "Feb",
  "Mar",
  "Apr",
  "May",
  "Jun",
  "Jul",
  "Aug",
  "Sep",
  "Oct",
  "Nov",
  "Dec",
];

export function formatDate(value: string | null | undefined): string {
  if (!value) return "—";

  // Date-only values (e.g. 2026-11-05) should not shift by timezone.
  const dateOnly = /^(\d{4})-(\d{2})-(\d{2})/.exec(value);
  if (dateOnly) {
    const [, year, month, day] = dateOnly;
    const date = new Date(Number(year), Number(month) - 1, Number(day));
    if (Number.isNaN(date.getTime())) return value;
    return `${MONTHS[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()}`;
  }

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return `${MONTHS[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()}`;
}

export function formatDateTime(value: string | null | undefined): string {
  if (!value) return "—";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return `${formatDate(value)} · ${date.toLocaleTimeString([], {
    hour: "numeric",
    minute: "2-digit",
  })}`;
}

export function timeAgo(value: string): string {
  const date = new Date(value);
  const seconds = Math.floor((Date.now() - date.getTime()) / 1000);
  if (seconds < 60) return "Just now";
  const minutes = Math.floor(seconds / 60);
  if (minutes < 60) return `${minutes}m ago`;
  const hours = Math.floor(minutes / 60);
  if (hours < 24) return `${hours}h ago`;
  const days = Math.floor(hours / 24);
  if (days < 7) return `${days}d ago`;
  return formatDate(value);
}

export function getErrorMessage(error: unknown): string {
  if (axios.isAxiosError<ValidationErrors>(error)) {
    const firstError = error.response?.data?.errors
      ? Object.values(error.response.data.errors)[0]?.[0]
      : undefined;
    return (
      firstError ??
      error.response?.data?.message ??
      error.message ??
      "Something went wrong. Please try again."
    );
  }
  if (error instanceof Error) return error.message;
  return "Something went wrong. Please try again.";
}

export function initials(name: string): string {
  return name
    .split(" ")
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() ?? "")
    .join("");
}

export function toDateTimeInput(date: Date): string {
  const offset = date.getTimezoneOffset();
  return new Date(date.getTime() - offset * 60_000).toISOString().slice(0, 16);
}
