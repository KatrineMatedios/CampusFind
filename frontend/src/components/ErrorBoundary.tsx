import { Component, type ErrorInfo, type ReactNode } from "react";

interface Props {
  children: ReactNode;
}

interface State {
  error: Error | null;
}

export default class ErrorBoundary extends Component<Props, State> {
  state: State = { error: null };

  static getDerivedStateFromError(error: Error): State {
    return { error };
  }

  componentDidCatch(error: Error, info: ErrorInfo) {
    console.error("CampusFind render error:", error, info.componentStack);
  }

  render() {
    if (!this.state.error) return this.props.children;

    const error = this.state.error;

    return (
      <div className="min-h-screen bg-brand-cream p-6 font-sans text-stone-800">
        <div className="mx-auto max-w-3xl rounded-3xl bg-white p-6 shadow">
          <h1 className="text-xl font-bold">CampusFind hit an error</h1>
          <p className="mt-2 text-sm">
            Please copy the details below and send them to your developer.
          </p>
          <pre className="mt-4 max-h-96 overflow-auto rounded-2xl bg-stone-900 p-4 text-xs text-stone-100">
{error.stack ?? error.message}
          </pre>
          <button
            type="button"
            className="mt-4 rounded-xl bg-brand-brown px-4 py-2 text-sm font-semibold text-white"
            onClick={() => {
              localStorage.clear();
              window.location.reload();
            }}
          >
            Clear session and reload
          </button>
        </div>
      </div>
    );
  }
}
